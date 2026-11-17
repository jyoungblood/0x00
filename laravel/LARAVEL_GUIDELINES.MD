# Laravel Development Standards & Guidelines

## 1. General Principles

- **Keep it simple.** Favor straightforward, readable, and maintainable code over unnecessary complexity.
- **Write human-readable code.** Code should be easy to understand, follow, and modify without extensive explanation.
- **Follow established Laravel conventions.** Prefer idiomatic Laravel patterns, standard architecture, and community-accepted best practices.
- **Use the appropriate level of abstraction.** Avoid overengineering, unnecessary layers, premature abstractions, and overly clever solutions.
- **Favor convention over customization.** Use Laravel's standard approaches and built-in capabilities wherever practical.

## 2. Architecture & Code Quality

- Follow established Laravel design patterns, naming conventions, and coding standards.
- Prefer conventional, well-understood solutions over unusual or highly customized implementations.
- Keep the codebase organized, consistent, and easy to navigate.
- Prioritize performance, efficiency, security, readability, and maintainability.
- Introduce abstractions only when they solve a real problem or meaningfully improve the code.
- Avoid unnecessary dependencies, indirection, duplication, and complexity.
- Preserve existing functionality when refactoring or improving code.

## 3. Packages & Dependencies

Prefer established, trusted packages over custom implementations whenever practical.

Use the following order of preference:

1. **Laravel first-party solutions:** Official Laravel features, components, and packages.
2. **Trusted community packages:** Well-maintained, widely adopted third-party packages with strong community support and a proven track record.
3. **Custom implementations:** Build our own solutions when existing options are unsuitable or introduce unnecessary complexity.

Favor packages that follow Laravel conventions and integrate naturally with the framework. Avoid reinventing functionality already handled well by established solutions.

## 4. Laravel Boost & Documentation

Use [Laravel Boost](https://laravel.com/ai/boost) as a primary resource for Laravel-specific development guidance and application context.

- Use Laravel Boost's documentation search to reference current, version-appropriate Laravel documentation and package-specific best practices.
- Use Boost's available tools to inspect the application's configuration, routes, database schema, models, and other relevant context before making assumptions.
- Consult Boost when implementing unfamiliar functionality, evaluating architectural approaches, or determining the idiomatic Laravel solution.
- Prefer verified documentation and actual application context over assumptions or potentially outdated knowledge.
- Follow the conventions and recommendations appropriate to the project's installed Laravel version and dependencies.
- Use Boost where relevant and practical without introducing unnecessary tool calls or disrupting straightforward development tasks.

## 5. Frontend Navigation & Prefetching

Use the standard navigation and prefetching capabilities provided by the application's frontend framework to improve perceived performance and responsiveness.

- **Prefetch normal navigation links.** Where appropriate, prefetch destinations for standard navigation links and linked interface elements so pages are ready when users navigate.
- **Use framework-native methods.** Follow the recommended prefetching approach for the frontend technology in use, such as Livewire, Inertia, or another framework. Prefer built-in capabilities over custom JavaScript or third-party solutions.
- **Choose appropriate prefetch triggers.** Use hover, intent, viewport visibility, or other supported mechanisms based on the framework's conventions and the expected navigation behavior.
- **Only prefetch safe, read-only requests.** Never prefetch actions that modify application state or produce side effects.
- **Do not prefetch form controls or action buttons.** This includes submitting forms, saving changes, deleting records, logging out, or any other state-changing operation.
- **Avoid unnecessary requests.** Consider bandwidth, server load, and the cost of fetching data. Do not aggressively prefetch expensive pages or large amounts of content without a clear benefit.
- **Preserve normal navigation behavior.** Prefetching should improve responsiveness without changing application functionality, security, or expected browser behavior.

## 6. Testing Philosophy

Maintain a lean, practical test suite focused on protecting important application behavior.

- Prefer end-to-end and feature-level tests over isolated unit tests.
- Test meaningful functionality, critical workflows, and behavior that needs ongoing regression protection.
- Write temporary tests as needed when developing, debugging, or validating new features.
- Not every change requires a permanent test.
- Remove temporary, redundant, or low-value tests once they are no longer useful.
- Avoid testing implementation details or maintaining tests solely for coverage metrics.
- Keep the permanent test suite focused, efficient, and maintainable.

## 7. Project Context & Existing Functionality

Before making architectural recommendations or significant changes:

- Read the product definition, project documentation, and roadmap.
- Understand the application's intended functionality, current behavior, and planned features.
- Consider future requirements without prematurely implementing or overengineering for them.
- Preserve existing functionality unless a change is explicitly requested.
- Treat temporarily disabled functionality as intentional, not necessarily obsolete. For example, user registration is disabled during beta but must remain available for reactivation afterward.

## 8. Code Review & Improvements

When reviewing or assessing the codebase:

- Evaluate adherence to these standards and established Laravel best practices.
- Identify unnecessary complexity, nonstandard patterns, redundant code, security concerns, and performance issues.
- Look for opportunities to simplify implementations using built-in Laravel functionality or trusted packages.
- Recommend improvements that meaningfully increase clarity, consistency, maintainability, or reliability.
- Avoid proposing changes solely for stylistic preferences or theoretical architectural purity.
- Prioritize practical improvements while preserving existing application behavior.
- Explain recommendations clearly, including why a change is beneficial and how it aligns with standard Laravel practices.

**Overall objective:** Build and maintain a clean, secure, performant, and understandable Laravel application using conventional, community-accepted solutions. Prefer the simplest appropriate implementation that follows the standard Laravel happy path, and introduce additional complexity only when genuinely necessary.