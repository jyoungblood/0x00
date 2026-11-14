#!/usr/bin/env python3
"""Rehearse PHP-FPM symlink switching in disposable files outside the web root.

Usage: python3 check-fpm.py SOCKET PRIVATE_PARENT USER_INI [--inspect-only]
No application, database, queue, mail or storage integrations are loaded.
"""

import concurrent.futures
import json
import os
from pathlib import Path
import shutil
import socket
import struct
import sys
import tempfile
import time


def record(kind, content=b''):
    return struct.pack('!BBHHBB', 1, kind, 1, len(content), 0, 0) + content


def parameter_length(value):
    length = len(value)
    return bytes([length]) if length < 128 else struct.pack('!I', length | 0x80000000)


def parameter(key, value):
    key, value = key.encode(), value.encode()
    return parameter_length(key) + parameter_length(value) + key + value


def receive(connection, length):
    data = b''
    while len(data) < length:
        chunk = connection.recv(length - len(data))
        if not chunk:
            raise RuntimeError('FPM closed the connection before completing its response.')
        data += chunk
    return data


def request(socket_path, webroot, hold=False):
    parameters = {
        'GATEWAY_INTERFACE': 'CGI/1.1', 'REQUEST_METHOD': 'GET',
        'SCRIPT_FILENAME': str(webroot / 'index.php'), 'SCRIPT_NAME': '/index.php',
        'DOCUMENT_ROOT': str(webroot), 'REQUEST_URI': '/index.php' + ('?hold=1' if hold else ''),
        'QUERY_STRING': 'hold=1' if hold else '', 'SERVER_PROTOCOL': 'HTTP/1.1',
        'SERVER_NAME': 'example.com', 'SERVER_PORT': '443', 'HTTPS': 'on',
        'REMOTE_ADDR': '127.0.0.1', 'CONTENT_LENGTH': '0',
    }
    content = b''.join(parameter(key, value) for key, value in parameters.items())
    stdout = b''
    stderr = b''
    with socket.socket(socket.AF_UNIX, socket.SOCK_STREAM) as connection:
        connection.settimeout(10)
        connection.connect(socket_path)
        connection.sendall(record(1, struct.pack('!HB5x', 1, 0)) + record(4, content) + record(4) + record(5))
        while True:
            version, kind, request_id, length, padding, _ = struct.unpack('!BBHHBB', receive(connection, 8))
            if version != 1 or request_id != 1:
                raise RuntimeError('Unexpected FastCGI response.')
            body = receive(connection, length)
            receive(connection, padding)
            if kind == 6:
                stdout += body
            elif kind == 7:
                stderr += body
            elif kind == 3:
                application_status, protocol_status = struct.unpack('!IB3x', body)
                if application_status or protocol_status or stderr:
                    raise RuntimeError('FPM probe failed; no application code was loaded.')
                break
    headers, separator, body = stdout.partition(b'\r\n\r\n')
    if not separator or b'Status: 500' in headers:
        raise RuntimeError('FPM did not return a valid probe response.')
    return json.loads(body)


def switch(directory, release):
    temporary_link = directory / 'current.next'
    temporary_link.symlink_to(directory / 'releases' / release)
    os.replace(str(temporary_link), str(directory / 'current'))


def verify(result, release):
    if (result['entry'], result['before'], result['after']) != (release, release, release):
        raise RuntimeError('A request used stale or mixed release files: ' + json.dumps(result))
    expected = {'opcache.validate_timestamps': '1', 'opcache.revalidate_freq': '0', 'opcache.revalidate_path': '1'}
    if result['ini'] != expected or result['sapi'] != 'fpm-fcgi' or not result['open_basedir']:
        raise RuntimeError('Required FPM settings are not effective: ' + json.dumps(result))


def main():
    if len(sys.argv) not in (4, 5) or (len(sys.argv) == 5 and sys.argv[4] != '--inspect-only'):
        raise RuntimeError(__doc__)
    socket_path, parent, ini_path = sys.argv[1:4]
    parent = Path(parent).resolve()
    parent.relative_to(Path.home().resolve())
    if not Path(ini_path).is_file():
        raise RuntimeError('The selected .user.ini does not exist.')
    with tempfile.TemporaryDirectory(prefix='.fpm-probe-', dir=str(parent)) as temporary:
        directory = Path(temporary)
        for release in ('a', 'b'):
            public = directory / 'releases' / release / 'public'
            public.mkdir(parents=True)
            shutil.copyfile(ini_path, str(public / '.user.ini'))
            (public.parent / 'marker.php').write_text("<?php return '" + release + "';\n")
            source = r'''<?php
$before = require __DIR__.'/../marker.php';
if (isset($_GET['hold'])) {
    file_put_contents(__DIR__.'/../started', '1');
    usleep(700000);
}
header('Content-Type: application/json');
echo json_encode([
    'entry' => '__RELEASE__', 'before' => $before,
    'after' => require __DIR__.'/../marker.php',
    'pid' => getmypid(), 'sapi' => PHP_SAPI,
    'open_basedir' => ini_get('open_basedir'),
    'ini' => array_combine(
        ['opcache.validate_timestamps', 'opcache.revalidate_freq', 'opcache.revalidate_path'],
        array_map('ini_get', ['opcache.validate_timestamps', 'opcache.revalidate_freq', 'opcache.revalidate_path'])
    ),
]);
'''
            (public / 'index.php').write_text(source.replace('__RELEASE__', release))
            for script in (public / 'index.php', public.parent / 'marker.php'):
                os.utime(str(script), (1000000000, 1000000000))
        switch(directory, 'a')
        webroot = directory / 'public_html'
        webroot.symlink_to(directory / 'current' / 'public')
        initial = request(socket_path, webroot)
        print('Effective FPM settings: ' + json.dumps({'sapi': initial['sapi'], 'ini': initial['ini'], 'open_basedir_enabled': bool(initial['open_basedir'])}))
        if len(sys.argv) == 5:
            return
        verify(initial, 'a')
        pids = {initial['pid']}
        for release in ['a', 'b'] * 10:
            switch(directory, release)
            for _ in range(3):
                result = request(socket_path, webroot)
                verify(result, release)
                pids.add(result['pid'])
        switch(directory, 'a')
        with concurrent.futures.ThreadPoolExecutor(max_workers=1) as executor:
            pending = executor.submit(request, socket_path, webroot, True)
            deadline = time.monotonic() + 5
            while not (directory / 'releases' / 'a' / 'started').exists():
                if time.monotonic() > deadline or pending.done():
                    raise RuntimeError('The in-flight request did not reach the switch checkpoint.')
                time.sleep(0.01)
            switch(directory, 'b')
            verify(pending.result(timeout=10), 'a')
        verify(request(socket_path, webroot), 'b')
        print('Passed: 20 atomic switches / 60 follow-up requests across ' + str(len(pids)) + ' FPM workers; in-flight request stayed on its original release.')
    print('Temporary probe files removed. Apache routing and application data were untouched.')


if __name__ == '__main__':
    try:
        main()
    except Exception as exception:
        print('FPM rehearsal failed: ' + str(exception), file=sys.stderr)
        sys.exit(1)
