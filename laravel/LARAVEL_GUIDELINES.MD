# Laravel Development Standards & Guidelines

## 1. General Principles

- **Keep it simple.** Favor straightforward, readable, and maintainable code over unnecessary complexity.
- **Write human-readable code.** Code should be easy to understand, follow, and modify without extensive explanation.
- **Follow established Laravel conventions.** Prefer idiomatic Laravel patterns, standard architecture, and community-accepted best practices.
- **Use the appropriate level of abstraction.** Avoid overengineering, unnecessary layers, premature abstractions, and overly clever solutions.
- **Favor convention over customization.** Use Laravel's standard approaches and built-in capabilities wherever practical.

## 2. Architecture & Code Quality

- Prioritize performance, security, consistency, and maintainability.
- Use PHP types, return types, and typed properties wherever practical.
- Use DTOs for structured data and Action classes for business logic when they meaningfully improve clarity and organization.
- Keep controllers and presentation-layer components focused. Extract complex business logic when appropriate.
- Avoid unnecessary architectural layers, abstractions, and design patterns. Every abstraction should serve a clear purpose.

## 3. Packages & Dependencies

Prefer established solutions over custom implementations, in this order:

1. **Laravel first-party:** Official Laravel features and packages.
2. **Trusted community packages:** Well-maintained, widely adopted packages with strong community support.
3. **Custom implementations:** Only when existing solutions are unsuitable or unnecessarily complex.

Avoid reinventing functionality already handled well by established packages.

## 4. Laravel Boost & Documentation

- Use [Laravel Boost](https://laravel.com/ai/boost) for version-specific documentation, application context, and development guidance.
- Consult Boost's documentation search and application inspection tools before making assumptions about unfamiliar functionality or existing code.
- Follow recommendations appropriate to the project's installed Laravel version and dependencies.

## 5. Database & Queries

- Use Laravel migrations for all schema changes, with appropriate types, constraints, and indexes.
- Design clear, normalized schemas without unnecessary complexity.
- Prevent N+1 queries through appropriate eager loading.
- Keep queries efficient: retrieve only necessary data, use database-level filtering and aggregation, and paginate or chunk large datasets.
- Use transactions, constraints, and appropriate locking to maintain data integrity.
- Use parameterized queries and safe mass assignment practices.
- Avoid premature caching or optimization. Investigate actual query performance when needed.

## 6. Frontend Development

- Use reusable components for shared or complex UI, but avoid unnecessary component abstraction.
- Prefer TypeScript over JavaScript where practical, with appropriate typing for props and application data.
- Follow the conventions of the frontend framework in use (Livewire, Blade, Inertia, React, Vue, etc.).
- **Prefetch standard navigation links** using the framework's native prefetching capabilities and appropriate triggers (hover, intent, viewport, etc.).
- **Never prefetch state-changing actions**, including form submissions, save/delete operations, logout, or other action controls.
- Avoid aggressive prefetching that creates unnecessary requests or server load.

## 7. Testing Philosophy

Maintain a lean test suite focused on important application behavior.

- Prefer end-to-end and feature tests over unit tests.
- Test critical workflows and functionality that needs ongoing regression protection.
- Write temporary tests when useful during development, but not every change requires a permanent test.
- Remove temporary, redundant, or low-value tests.
- Avoid testing implementation details or chasing coverage metrics.

## 8. Project Context & Existing Functionality

- Read and understand the product definition, documentation, and roadmap before making significant changes.
- Preserve existing functionality unless explicitly instructed otherwise.
- Consider planned features without prematurely implementing or overengineering for them.
- Treat temporarily disabled features as intentional, not obsolete. For example, registration is disabled during beta but must be re-enabled afterward.

## 9. Code Review & Improvements

When assessing the codebase:

- Identify unnecessary complexity, nonstandard patterns, duplication, security issues, and performance problems.
- Recommend practical improvements using conventional Laravel solutions.
- Avoid changes driven solely by stylistic preferences or theoretical architectural purity.
- Prioritize meaningful improvements while preserving existing functionality.
- Briefly explain the rationale for recommended changes.

**Overall objective:** Build clean, secure, performant, and understandable Laravel applications using conventional, community-accepted solutions. Prefer the simplest appropriate implementation, with purposeful abstractions and minimal unnecessary complexity.
