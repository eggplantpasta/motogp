# AGENTS.md

## Project design philosophy

This application is intentionally built using simple, traditional PHP.

The primary goal is maintainability by developers who are comfortable with older, procedural-style PHP applications rather than modern PHP frameworks.

Prefer clarity, locality and explicit code over abstraction.

## Core principles

### Keep dependencies minimal

Avoid adding Composer packages unless they provide substantial value that would be difficult or risky to implement locally.

Before adding a dependency, consider whether the requirement can reasonably be implemented using:

* PHP standard library functionality
* SQLite
* existing project classes
* small amounts of local application code

Do not introduce frameworks, ORMs, dependency injection containers, routing frameworks, authentication frameworks or similar infrastructure without a strong reason.

### Keep page flow obvious

Where practical, one application page should consist of:

* one PHP controller file under `public/`
* one corresponding Mustache template under `templates/`

For example:

```text
public/user/account.php
templates/user/account.mustache
```

The PHP file should:

1. bootstrap the application
2. perform authentication/authorisation checks
3. process request data
4. call application/database classes where useful
5. prepare template data
6. render the corresponding template

A developer should be able to find the page behaviour by opening the PHP file that corresponds to the URL.

### Prefer simple classes over architecture layers

Reusable behaviour belongs in small classes under `src/`.

Use classes where they make code clearer or remove genuine duplication, but avoid introducing layers purely for architectural purity.

Do not create repositories, services, managers, DTOs, factories or interfaces unless the application has a real need for them.

For a small operation, a direct database query inside an appropriate application class is preferable to several abstraction layers.

### Keep SQL visible

This project uses SQLite directly.

Prefer explicit SQL that can be read and understood easily.

Do not introduce an ORM.

Database access should remain straightforward and should use parameterised queries for user-supplied values.

### Prefer server-rendered HTML

Pages should primarily be rendered on the server using Mustache templates.

Use JavaScript only where it meaningfully improves interaction.

Do not turn the application into a client-side application or introduce a frontend framework without a compelling reason.

### Follow the existing style

When changing existing functionality:

* preserve the current PHP + Mustache structure
* reuse existing project classes where appropriate
* make the smallest coherent change
* avoid unrelated refactoring
* avoid replacing working simple code with a more abstract pattern merely because it is considered modern

### Security still matters

Simple does not mean insecure.

Use normal modern security practices including:

* `password_hash()` and `password_verify()`
* parameterised SQL
* output escaping
* CSRF protection for state-changing requests
* appropriate session handling
* server-side authentication and authorisation checks
* validation of all externally supplied data

Security improvements should preferably be implemented in a way that remains easy to understand.

## Decision rule

When choosing between two reasonable implementations, prefer the one that a developer unfamiliar with modern PHP frameworks could understand by reading the relevant PHP file and template from top to bottom.
