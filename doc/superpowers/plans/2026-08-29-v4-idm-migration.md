# Advanced Privilege v4 IDM Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the legacy advanced-privilege bundle to a Portal-Web v4 module that performs all reads and mutations through IDM as the admin-authenticated user.

**Architecture:** Bootstrap the Portal-Web skeleton and retain only its v4 package infrastructure. Place the module's PSR-4 namespace directly below `app/src`; an IDM gateway builds SAT-authenticated HTTP requests and exposes focused read/write operations to the controller and autocomplete endpoint.

**Tech Stack:** Symfony 6.4, IServ Portal-Web Module/Authentication/Autocomplete bundles, `iserv/idm-api-client`, PHPUnit, Webpack, Debian/iservmake.

**Spec:** `doc/superpowers/specs/2026-08-29-v4-idm-migration-design.md`

## Global Constraints

- Module ID is `stsbl/advanced-privilege`; its route prefix is `/iserv/stsbl/advanced-privilege/`.
- Use `app/src` as the PSR-4 source root; do not create namespace directories below it.
- All module routes require `AUTHENTICATED_AS_ADMIN`; never use `ROLE_ADMIN` or a module-local privilege.
- IDM requests receive the request SAT as `X-IServ-Authentication`; no User-Backend, CoreBundle, direct IDM database access, or service-account fallback is permitted.
- Keep owner, privilege, and group-flag bulk operations, and add an admin-only God-Mode owner autocomplete endpoint.
- Add a self-contained SVG menu icon under `app/assets/img`.

---

### Task 1: Bootstrap the v4 package

**Files:** replace legacy package structure with Portal-Web skeleton files; preserve `LICENSE`, `debian/changelog`, and `doc/`.

- [ ] Initialize the repository from the current `portal-web-skeleton` with `igit init --skeleton portal-web-skeleton` and verify the generated package uses Symfony 6.4.
- [ ] Rename skeleton identifiers, console wrapper, package metadata, module ID, route prefix, and asset names to `stsbl-iserv-advanced-privilege` / `stsbl/advanced-privilege`.
- [ ] Merge Debian dependencies with the old package description instead of retaining the skeleton description.
- [ ] Run `git diff --check` and `iservmake run_tools`.

### Task 2: Create SAT-authenticated IDM gateway with tests

**Files:** create `app/src/IdmCredentials.php`, `app/src/IdmGateway.php`, DTOs below `app/src/Idm/`; tests below `app/tests/Unit/Idm/`.

- [ ] Write failing tests that assert the credentials append the current request SAT as `X-IServ-Authentication` and that the gateway emits the documented owner, privilege, and group-flag URLs/payloads.
- [ ] Implement the request-scoped credentials and gateway with `IdmClientInterface`, `ApiTokenCredentials`-compatible header behavior, JSON IRIs, `RawHydrator`/`NullHydrator`, and `RequestException` translation.
- [ ] Run the focused PHPUnit tests and then `iservmake phpunit`.

### Task 3: Implement v4 forms, controller, and God-Mode autocomplete

**Files:** create `app/src/Controller/AdvancedPrivilegeController.php`, `app/src/Controller/AdminAutocompleteController.php`, `app/src/Form/`, `app/src/Model/`; create `app/templates/advanced_privilege/index.html.twig`; tests below `app/tests/Functional/Controller/`.

- [ ] Write failing controller/form tests for `AUTHENTICATED_AS_ADMIN`, owner removal, mutation result JSON, autocomplete search, and selected UUID resolution.
- [ ] Implement UUID-based target/operation form models, v4 attribute routes, and the owner autocomplete using `AutocompleteTagsType` and an IDM-backed endpoint limited to admin-authenticated requests.
- [ ] Implement the previous all/prefix/suffix/contains/regular-expression targeting semantics in the gateway, validate malformed expressions before requests, and render form errors safely.
- [ ] Run focused tests and `iservmake phpunit`.

### Task 4: Add module integration, assets, translations, and SVG icon

**Files:** modify `iserv-module.json`, v4 security/routes/service configuration, `Makefile.iservmake`, locales; create `app/assets/img/advanced-privilege.svg`, JS/CSS as required.

- [ ] Write a simple, monochrome SVG combining a group silhouette and key motif, using currentColor, an accessible title, and no external resources.
- [ ] Register the icon and admin integration configuration; import Autocomplete bundle routes and require `AUTHENTICATED_AS_ADMIN` in security access control.
- [ ] Migrate existing gettext strings and frontend confirmation behavior; remove all `modules/` files and verify no `CoreBundle`, User-Backend, or legacy Bundle references remain.
- [ ] Run `iservmake lint tests` and `rg -n 'CoreBundle|User-Backend|GroupManager|modules/'` with zero production-code results.

### Task 5: Package and runtime verification

**Files:** Debian/package and CI files introduced by skeleton; tests and generated locale files when required.

- [ ] Run `composer dump-autoload`, `iservmake iservinstall`, and `iservchk` on the development VM if available.
- [ ] Exercise the admin route, owner autocomplete, one owner mutation, one privilege mutation, and one group-flag mutation in e2e/browser tooling when available.
- [ ] Run `iservmake lint tests`, inspect package/MR CI status after push, and fix migration failures before delivery.
