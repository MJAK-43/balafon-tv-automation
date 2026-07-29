# Balafon Broadcast Manager - UI Redesign Plan

## Source Audit

Audit based on:

- Current Vue layout and shared UI components
- Existing user screenshots of `Dashboard`, `Scheduling`, and blank-load states
- Current dark SaaS/broadcast implementation in `resources/js`

## Current Problems

### 1. Visual hierarchy is weak

- Too many panels share the same weight, radius, border, and glow treatment.
- Important actions do not stand out clearly from secondary actions.
- The eye does not know where to start on each screen.

### 2. The interface feels "assembled", not designed

- Sidebar, topbar, cards, pills, badges, and timeline blocks all use similar glass styles without a clear system.
- Orange glow is used almost everywhere, so it no longer communicates priority.
- The bottom floating navigation duplicates the left navigation and creates noise.

### 3. Scheduling is visually overloaded

- The timeline is dense and hard to parse quickly.
- Drag and drop cards are too small relative to the amount of information shown.
- The left controls and the right calendar compete for attention.
- Time semantics are not obvious enough for operators.

### 4. Dashboard is not operating-room grade

- It reads like an admin dashboard instead of a broadcast control console.
- Critical runtime information is fragmented.
- vMix status, automation status, alerts, and next action should be grouped more clearly.

### 5. Mobile and medium-width behavior need a rethink

- The current layout is desktop-first in a rigid way.
- On narrower screens, density and panel stacking will quickly become heavy.

## Screens Reviewed

### Scheduling

Observed issues from the screenshot:

- The left rail is visually heavy.
- The timeline blocks are hard to compare at a glance.
- The drag-source playlist list is separated too far from the scheduling action.
- Action buttons in the hero consume too much attention relative to the main calendar.

### Dashboard / Foundation screens

Observed issues from code and structure:

- Multiple card groups use nearly identical surfaces.
- No strong "mission control" centerpiece exists.
- Operational status is presented as generic stats rather than command context.

### App shell

- `Sidebar` and bottom quick-links duplicate navigation.
- The shell does not yet feel like a professional playout console.

## Recommended Design Direction

### Positioning

Target visual language:

- Broadcast control software
- Premium SaaS 2026
- Mission control + newsroom planning

Not target:

- Generic Tailwind admin
- Glassmorphism everywhere
- Neon gaming UI

### Style Direction

- Use a darker graphite base instead of blue-heavy gradients everywhere.
- Reserve one strong accent for actionable broadcast states.
- Introduce a second accent only for alerts and live/on-air states.
- Reduce glow and increase disciplined contrast.
- Use fewer border radii on structural containers.

### Color System

- Base background: deep graphite / carbon
- Surface 1: matte panel
- Surface 2: raised panel
- Accent primary: signal amber
- Accent live: broadcast red
- Accent success: control green
- Accent info: electric cyan used sparingly

### Typography

- Keep `Space Grotesk` only if paired with a calmer secondary text rhythm.
- Headings should be more editorial and less uniformly bold.
- Operational labels should use tighter uppercase metadata styling only where needed.

## New UI Architecture

### App Shell

Replace the current shell with:

- Left docked navigation rail
- Top operational command bar
- Main content canvas
- Optional right context rail on advanced pages

Rules:

- Remove the floating bottom navigation on authenticated screens.
- Keep only one primary navigation model.
- Add a compact operator/account block in the top bar.

### Navigation Structure

Primary:

- Overview
- Diffusion
- Scheduling
- Media
- Playlists
- System

Secondary contextual actions should move into page headers, not global nav.

## Page-by-Page Redesign

### 1. Dashboard -> "Overview"

Goal:

- A real command center, not a metric wall.

Layout:

- Top strip: connection health, automation health, local machine time, active channel
- Main hero: On Air / Next Up
- Secondary grid:
  - vMix runtime
  - latest alerts
  - recent automation events
  - quick actions

### 2. Diffusion

Goal:

- Make this the operational heart of the app.

Layout:

- Left: current playlist and current item
- Center: large on-air state block with progress
- Right: next items, next scheduled playlist, runtime alerts

Need:

- Stronger live state visuals
- Large readable timers
- Clear failure and recovery states

### 3. Scheduling

Goal:

- Planning should be clearer than drag-and-drop novelty.

Layout:

- Top: date/channel/time context
- Left column: planning form, quick actions, playlist library
- Main: calendar/timeline
- Optional right drawer: selected schedule details

Important changes:

- Make manual scheduling the primary workflow
- Keep drag-and-drop as a secondary shortcut
- Increase timeline readability
- Reduce the number of simultaneous chips and micro-labels

### 4. Media

Goal:

- Feel like a content operations library.

Layout:

- Top search and filters
- Main library grid/list
- Right details inspector with preview and metadata

Important changes:

- More visual separation between library and inspector
- Better type/status tags
- Cleaner dialog forms

### 5. Playlists

Goal:

- Editorial composition workflow, not raw CRUD.

Layout:

- Left: playlist list
- Center: selected playlist builder
- Right: metadata, duration, validation, quick actions

## Component System To Introduce

Create a tighter UI system around:

- `ShellFrame`
- `CommandBar`
- `SectionPanel`
- `MetricTile`
- `StatePill`
- `ActionButton`
- `InspectorPanel`
- `TimelineLane`
- `TimelineBlock`
- `EmptyPane`

Current shared components should be consolidated instead of multiplying card variants.

## Interaction Principles

- One primary action per zone
- No duplicated navigation
- No more than two accent colors active in the same block
- Timeline cards show only the most important information first
- Status meaning must be readable in 1 second

## Frontend Refactor Strategy

### Phase 1. Shell and design tokens

- Rebuild global tokens in `resources/css/app.css`
- Rework `App.vue`
- Rework `AppLayout.vue`
- Replace `Sidebar.vue` and `Topbar.vue`
- Remove floating bottom nav

### Phase 2. Shared components

- Normalize cards, buttons, panels, pills, empty states
- Reduce one-off visual patterns

### Phase 3. Critical screens

Implement in this order:

1. Diffusion
2. Dashboard
3. Scheduling
4. Media
5. Playlists

## Acceptance Criteria

- The app no longer looks like a generic Tailwind dashboard.
- The shell feels like professional broadcast software.
- The scheduling screen is readable before interacting with it.
- The diffusion screen becomes the operational centerpiece.
- Navigation is unified and simpler.
- Medium-width screens remain usable without visual collapse.

## Recommendation

Do not patch this screen by screen with isolated CSS tweaks.

Recommended approach:

1. Redesign the shell and token system first.
2. Build 6-8 strong shared UI primitives.
3. Rebuild `Diffusion`, `Dashboard`, and `Scheduling` on top of that system.
4. Then align `Media` and `Playlists`.

This will produce a coherent product. Incremental cosmetic tweaks on the current base will not.
