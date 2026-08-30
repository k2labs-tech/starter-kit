# Website specification — base-tenant starter kit

The public site for the starter kit. A sibling specification lives in the
`base/tenant` repository for the package's own site. The two share a design
system and cross-link, but they are **not the same site with two skins**: they
are read by people in different moments, deciding different things.

**Status:** specification, nothing built.
**Last reviewed:** 2026-08-30.

---

## 1. The difference from the package site, and why it matters

The package site is read by someone deciding **build or buy**. They are
sceptical, they have time, and they want evidence.

This site is read by someone who has already decided to start something and is
typing `laravel new` **today**. They are impatient, they are comparing against
Laravel's own starter kits and against Jetstream, and they will give the page
about forty seconds.

If this site argues, it loses. Its job is to show what exists after one command
and let the reader picture their Monday.

## 2. Who arrives

A developer starting a new B2B SaaS this week. Often a founding engineer or a
consultancy beginning a client project. They know Laravel, they have used a
starter kit before, and their reference point is "Breeze gives me auth" — so
the whole pitch is how much further this goes.

They are not evaluating a foundation for a five-year platform. That reader is
on the package site, and the two sites link to each other for exactly that
reason.

## 3. The one job

**Get them to run the command.**

Every page is measured against that. A page that does not move somebody closer
to a terminal is a page to cut.

## 4. What actually sells it

In this order:

1. **The command, immediately.** Visible above the fold, copyable in one click.
   Not behind a "Get started" button — the command *is* the call to action.
2. **What exists after it runs.** Accounts, per-account roles and permissions,
   navigation, feature flags, 2FA, invitations, billing, an audit trail, and
   twelve capability modules. On the first commit, before any of their code.
3. **The screens.** This is the one place screenshots earn their space: the
   user table, the role matrix, the file library, in light and dark. A starter
   kit is judged on what it looks like, and this one has a designed interface
   rather than scaffolded forms.
4. **The generator.** `k2labs-base:make-module Booking --fields="..."` writing a
   whole tenant-scoped CRUD vertical with its tests. This is the moment a
   reader stops comparing against Breeze.
5. **The exit.** `k2labs-base:scaffold` and `eject` — the code becomes theirs
   and the package leaves. Nobody adopting a foundation is unafraid of being
   trapped in it, and answering that unprompted buys more trust than any
   feature.

## 5. Structure

Small. Four pages plus documentation.

```
/                     Landing
/what-you-get         The full surface, with screenshots
/docs                 Getting started, then into the package's manual
/pricing              Licence and price (shared model with the package)
```

Anything beyond this is scope that competes with the command.

### Landing

- The command, above the fold, copyable.
- One sentence on what comes out.
- The screens, as a small gallery. Real screenshots, not mockups.
- The capability grid: core plus the twelve modules, one line each.
- The two questions every adopter has, answered inline rather than in a FAQ:
  *can I get out?* (scaffold and eject) and *what does it cost?*
- A link to the package site for the reader who wants the depth.

### /what-you-get

The long version of the grid, still one page. Each capability: one line on what
it is, and the two or three lines of code that use it. A reader here is
checking whether the thing they need is already handled.

Includes the screens at full size, and the generated module — showing the
command and the files it produces is more convincing than describing it.

### /docs

Getting started belongs to this site: create the project, answer the
installer's questions, run it. Everything past "it runs" links into the
package's documentation rather than being copied here, because a copy is a
thing that goes stale.

## 6. Build

Same static pipeline and the same design system as the package site, different
accent colour. Sharing the components is worth more than the small amount of
independence given up: two sites that look like one product family reinforce
each other, and the maintenance cost of two design systems is paid forever.

Screenshots are the one real asset to produce, and they need care:
- Real data, plausible names, not `test@example.com` and `Lorem ipsum`.
- Light and dark, switching with the site.
- Retina, and updated on any release that changes a screen — put this in the
  release checklist or they will be a year out of date and quietly undermine
  the claim that the interface is looked after.

## 7. What not to build

- A hosted demo. It costs real maintenance and invites judgement of the demo
  data. Screenshots plus the real command do the job.
- A tutorial series before anyone has shipped with it.
- A second copy of the package's documentation.
- Any comparison table naming Jetstream or Filament. The reader is already
  making that comparison and does not need help; a table that flatters us reads
  as a table written by us.

## 8. Done when

1. The command is visible and copyable without scrolling, on a phone.
2. A developer can see what the interface looks like before installing
   anything.
3. "Can I leave?" is answered on the landing page.
4. Getting started works, followed literally, on a clean machine.
5. Screenshots are in the release checklist, so they cannot silently rot.
