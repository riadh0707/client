# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

Next.js 15 (App Router) + TypeScript + Tailwind v4 + shadcn/ui, with Prisma + SQLite
for persistence. Chosen by the user from an offered set. SQLite is the demo-portable
target; the Prisma schema stays Postgres-compatible so the datasource can be switched
without a model rewrite.

Delivery context: **validated project, production foundation** — not a throwaway pitch.
Architectural soundness, tests, and extensibility outrank demo spectacle where the two
conflict.

## Users

**Patients (primary).** Algerian residents looking for a health professional or
facility. Two distinct situations, and they are not the same job:

- *Urgent/local need* — "a pharmacy open right now near me", "a lab in El Oued".
  Success is a phone number, an address, and opening hours in seconds. Often on
  mobile, often on a poor connection, sometimes one-handed on the street.
- *Considered choice* — "a cardiologist in Oran", picking a dentist for a family
  member. Success is a profile credible enough to trust, then a booked appointment.

**Health professionals and facilities (secondary, revenue-bearing).** Doctors,
dentists, pharmacies, analysis labs, and medical imaging centres (radiology, scanner,
MRI, ultrasound). They manage their own presence: profile, schedule, availability,
appointments, subscription. Many are not comfortable with software; the professional
space must survive low digital literacy.

**Secretaries (delegated role).** Staff who operate a practice's agenda on the
professional's behalf. Strictly fewer permissions than the professional: agenda,
appointments, availability, and the practice information needed to do that — no
subscription, billing, or profile-identity control.

**Administrators (the client's own team).** Operate the platform: onboard and verify
partners, manage subscriptions, moderate, and read activity. This is a working
back-office, not a vanity dashboard.

## Product Purpose

Connect Algerian patients with health professionals and facilities, and give those
professionals a managed online presence worth paying for.

Success is measured on both sides: a patient finds and reaches the right professional
without leaving the platform, and a professional receives appointments they would not
otherwise have received.

## Positioning

Not a doctor directory. A multi-category Algerian health ecosystem where a pharmacy,
an MRI centre, and a cardiologist are first-class citizens of the same system — with
Wilaya→Commune geography as the native spine rather than a bolted-on country filter.

The defensible mechanism is the generic professional/establishment model: adding
"physiotherapist" or "clinic" is configuration and content, not a new codebase.

## Operating Context

- **Geography is the primary axis of every search.** Wilaya → Commune, everywhere:
  profiles, search, filters, admin. Geolocation is optional and must degrade to
  manual Wilaya/Commune selection without loss of function.
- **Mobile-first reality.** Patients search on phones, frequently outdoors, on
  variable networks. The mobile experience is designed on its own terms, not derived
  by shrinking desktop.
- **French interface in v1**, with Arabic (and RTL) as a planned addition. Copy,
  layout, and component APIs must not make that addition expensive.
- **Algerian conventions**: Algerian phone formats, DZD for any price.
- Professionals' operating hours drive real patient decisions ("open now", "next
  opening") — hours are functional data, not decoration.

## Capabilities and Constraints

**Confirmed scope.** Role-based access (patient / professional / secretary / admin);
multi-category professional and establishment profiles; search by name, specialty,
type, Wilaya, Commune, proximity, service, availability; nearby discovery with list
and map views; appointments with lifecycle statuses (pending, confirmed, completed,
cancelled, no-show); professional availability and schedule management; favourites;
notifications; professional verification workflow; subscriptions with plans and
expiry; sponsored placement; admin back-office with partner, user, subscription,
appointment, statistics, and activity-log sections.

**Constraints and honesty rules.**

- **No payment provider chosen.** Build the subscription architecture properly and
  use a clearly-labelled mock for the transaction step. Do not simulate a real
  payment flow.
- **Sponsored results must be visibly distinguishable** from organic ones. Search
  ranking must not be covertly manipulated.
- **Demo data is fictional.** No real person may be used as a fake partner. Data must
  be realistic enough that the product does not read as an empty shell.
- Build order agreed with the user: foundations and design system, then the patient
  journey (landing, role choice, search, profiles, booking), then the professional
  space, then administration.

**Terminology.** *Wilaya* (province), *Commune* (municipality), *professional*
(individual practitioner), *establishment* (pharmacy, lab, imaging centre),
*partner* (either, in admin context).

## Brand Commitments

Name: **DOCTORY**. No existing logo, palette, typography, or brand guideline — the
user confirmed a blank slate, so the visual identity is to be created. No inherited
constraint to preserve.

## Evidence on Hand

- **None yet.** No real partners, no real patients, no testimonials, no usage
  figures, no press, no pricing validated with the client. Nothing of the kind may be
  fabricated or implied anywhere in the interface.
- Algerian Wilaya/Commune reference data is to be sourced from an open dataset
  (58 wilayas with their communes and coordinates) rather than invented; a
  hand-written subset is the fallback if the network blocks retrieval.
- Subscription plan names, prices, and tiers are **undecided** and must be presented
  as placeholders until the client sets them.

## Product Principles

1. **Geography before everything.** If a screen shows a professional, it answers
   "where" before it answers anything else.
2. **Two speeds, one product.** Urgent lookup and considered choice are distinct
   jobs; neither may be sacrificed for the other.
3. **Category-agnostic by construction.** Nothing may assume "doctor" is the only
   kind of partner. A new partner type must not require a schema migration of
   existing types.
4. **Trust is earned visibly.** Verification status, sponsorship, and availability
   are stated plainly. The platform never implies more certainty than it has.
5. **Degrade honestly.** Refused geolocation, absent hours, unverified profile,
   no results — each has a designed state that keeps the user moving.

## Accessibility & Inclusion

Health services must reach users with low digital literacy, older users, and users on
small or low-quality screens. Required: sufficient contrast, real labels, full
keyboard navigation, visible focus states, comprehensible error messages, and touch
targets sized for imprecise one-handed use. Layout and component APIs must not
foreclose RTL.
