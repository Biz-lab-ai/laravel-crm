# Fork copy of venturedrake/laravel-crm

- Upstream: https://github.com/venturedrake/laravel-crm
- Upstream commit: ff83a8cf (release 2.4.7), exported with `git archive ff83a8cf` (export-ignore applied, same as the Packagist dist).
- Why a copy: upstream pins `spatie/laravel-permission: ^6.0`; this application requires `^8.3`.
  Composer resolves dependency constraints before any file patch can apply, so the only way to
  install is a package copy with a relaxed constraint (see /root/agents/nariady/plan-crm-venturedrake-13-10.md, decision 2).
- Changes against upstream (composer.json, guarded blocks in the service provider — items 4 and 8, and three host hooks — item 7):
  1. `"spatie/laravel-permission": "^6.0|^7.0|^8.0"` (was `^6.0`) — see above.
  2. `"version": "2.4.7"` added (PathPackagesCarryVersionTest requires every path package to name its version).
  3. `php-flasher/flasher-laravel` removed from `require`: its global helper `flash()` collides with the
     host application's `spatie/laravel-flash` helper of the same name (`PHP Fatal error: Cannot redeclare
     function flash()` during `composer install`). The CRM core calls `flash()` only inside its own
     Blade/Livewire UI (Http/Controllers, Http/Livewire, Livewire/Auth, Traits/NotifyToast), which this
     application keeps switched off (`laravel-crm.user_interface = false`, so those routes are never
     registered). The Filament plugin does not reference php-flasher at all.
  4. `src/LaravelCrmServiceProvider.php`: the `crm/api/v2` route group is wrapped in
     `if (config('laravel-crm.api.enabled', true))`. Upstream registers the token API unconditionally
     (the web UI already sits behind `user_interface`); its tenancy rests on the `X-Team-ID` header and
     `$user->allTeams()`, which this host does not have, so the wrapper shelf sets `api.enabled=false`
     until it has fenced the API itself. Default stays true — upstream behaviour is unchanged; goes
     into the upstream PR together with the spatie constraint.
  5. `"saasykit/laravel-money": "^1.1"` replaces `"cknow/laravel-money": "^7.0|^8.0"`. The host already
     installs saasykit/laravel-money, a copy of cknow/laravel-money under the same `Cknow\\Money\\`
     namespace (`diff -r` of both `src/` trees at cknow v8.5.0 / saasykit 1.1.0 is empty). Installing
     both put two directories behind one PSR-4 prefix; which class loads would depend on autoloader order.
  6. `AGENTS.md`, `CLAUDE.md` and `plan-filamentPlugin.prompt.md` removed (`CLAUDE.md` was never tracked:
     the root .gitignore drops it). They are upstream's instructions to coding agents, not package code:
     inside this repository they would steer our own agents working under forks/, and `AGENTS.md` cites
     the `Feature` test suite name that our platform guard forbids
     (EveryFeatureDirectoryIsInExactlyOneImpactSuiteTest). `diff -r` against a fresh export shows exactly
     these three files as missing.
  7. Host hooks (plan item 0.6, decisions В-10/В-12/В-13). All three default to upstream behaviour and go into
     the upstream PR together with the spatie constraint (plan item 0.2):
     - `laravel-crm.team_resolver` — a class name implementing the new `Contracts\ResolvesTeam`; default
       `Support\CurrentUserTeamResolver` returns `auth()->user()->currentTeam->id` as before.
       `BelongsToTeams` (stamping on creating) and `BelongsToTeamsScope::apply` read the team id through
       `Support\TeamResolution`. Why: this host keeps the team on its own session, not on `currentTeam`.
     - `laravel-crm.schedule.enabled` (default true) wraps the package's scheduler block in the provider.
       Why: the wrapper shelf schedules only what it has fenced.
     - `laravel-crm.number_generator` — a class name implementing the new `Contracts\GeneratesNumbers`;
       `NumberGeneratorService::next/reset` delegate to it, null keeps the upstream cache counter. The
       contract opens no transactions: the caller that creates the document owns it. Why: a cache counter
       loses numbers on cache flush; the host binds a database-backed generator.
     Class names, never closures: `config:cache` writes the config with var_export and cannot restore a closure.
  8. `laravel-crm.load_update_migrations` (default true) wraps the provider's `loadMigrationsFrom(database/updates)`.
     Why: those seven migrations are dated 2026-06..08, before the host's schema-dump restoration tail; loaded
     as-is they run before that tail on a fresh database (SchemaDumpTest rejects backdated pending migrations).
     The wrapper shelf sets the flag to false and owns the CRM schema itself (plan item 1.9). Goes into the
     upstream PR with item 7.
  9. Known upstream semgrep debt, marked in place: 13 lines of `src/` (empty catch, silent `return` in a catch,
     `dd()` in `Integrations/XeroController`) carry a same-line `// nosemgrep: <rule id> -- upstream 2.4.7 debt,
     README-FORK`. The rules of `.semgrep/rules` block only new findings against the PR base, and the base of the
     import PR has no upstream code at all, so every upstream line counted as new. The marker names one rule on one
     line: any other line of the copy, our patches of items 4, 7 and 8 included, stays scanned. List:
     `grep -rn "nosemgrep" forks/venturedrake/laravel-crm/src`. Re-apply after an upgrade only where the line is
     still upstream's.
  10. Input contract of the document PDFs (plan CRM 2.1i, crm-2-1i-1). Every unescaped text output of the 39 blade files
      reachable from the 25 entry views of `Support\PdfTemplateRegistry` (`resources/views/pdfs/**`, the five old
      `{doc}/pdf.blade.php`, `layouts/document.blade.php`) - 72 outputs `{!! nl2br($field) !!}` of description, terms,
      delivery_instructions, contactDetails and paymentInstructions - is `{!! nl2br(e($field)) !!}`: line breaks kept,
      markup escaped, so a document field can never bring an SVG, a stylesheet, a font or a remote image to the PDF
      engine. The only raw output left is the package stylesheet inlined by the layout. The shelf test
      CrmPdfInputClosureTest pins the closure and the rule. Goes into the upstream PR.
  11. `Support\PdfLogo::src()` reads the MIME type of the logo from its bytes (`finfo`, plan CRM 2.1i, crm-2-1i-1) and
      inlines only image/png, image/jpeg, image/gif and image/webp; any other content under any name is no logo (null,
      the templates fall back to the organisation name). Upstream took the type from the file name. Goes into the
      upstream PR.
  12. `Support\PdfLogo::src()` reads the logo from the disk named by `laravel-crm.upload_disk` (default `public`, the disk the
      uploads of the package already go to), not from the hard-coded `public` disk (plan CRM 2.4, crm-2-4-6): the wrapper
      shelf stores the logo on its closed disk. Goes into the upstream PR.
  13. `resources/views/layouts/document.blade.php` reads the inlined stylesheet from `config('laravel-crm.pdf_document_css')`
      first (plan CRM 2.1i, crm-2-1i-2) and falls back to the published `public/vendor/laravel-crm/css/document.css`
      as upstream: a host that does not publish the assets points the key at the package stylesheet. Goes into the
      upstream PR.
- Do not patch anything else here. Behaviour changes live in packages/bizai/crm-desk (the wrapper shelf).
- Upgrading: `git archive <new sha>` from upstream into this directory, re-apply the one-line
  constraint change and the version field, update this file, verify with `diff -r` against a fresh export.
- Pending upstream PR: relax the spatie constraint (plan item 0.2, after the owner's word).
- Not auto-discovered: both vendor providers are listed in the root composer.json under
  `extra.laravel.dont-discover`. With the packages installed but the wrapper shelf absent the
  application stays inert (no `laravel-crm` config, no `crm/api/v2` routes, no migrations loaded).
  The wrapper shelf `packages/bizai/crm-desk` registers the two providers explicitly after it has set
  the `laravel-crm.*` config it needs (teams, host team model, modules, user_interface=false).
  The same list holds the CRM's transitive packages that ship auto-discovered providers:
  robsontenorio/mary (its unprefixed Blade components such as `x-accordion` shadow the host's own
  components), codeat3/blade-forkawesome, mallardduck/blade-boxicons, owenvoke/blade-fontawesome,
  rinvex/countries and kirschbaum-development/eloquent-power-joins. The wrapper shelf registers the ones
  the CRM needs, with Mary under a component prefix.
