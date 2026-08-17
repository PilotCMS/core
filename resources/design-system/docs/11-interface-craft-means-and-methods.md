# Interface Craft: Means & Methods

Pilot uses Interface Craft's Means & Methods philosophy as a quality bar for the Jaunt design system. The product should feel considered, clear, and dependable without adding ornamental complexity or changing application behavior.

## Product principles

### Reduce until it is clear

- Give every page one primary heading and one obvious next action.
- Remove duplicate labels, headings, dividers, and controls that do not improve comprehension.
- Use progressive disclosure for secondary tools and advanced options.
- Let spacing and hierarchy do the work before adding decoration.

### Refine until it is right

- Start with Jaunt components and semantic tokens; do not invent a parallel visual language.
- Reuse established patterns for headers, cards, forms, menus, empty states, and feedback.
- Keep neutral interface chrome neutral. Reserve blue for links, focus, selection, and data meaning.
- Review the whole interaction, including hover, focus, active, disabled, loading, empty, error, success, and dark-mode states.

### Anticipate the user's needs

- Keep the user's place and context visible while they work.
- Show immediate, specific feedback for actions that take time or change state.
- Write concise labels and helper text that answer the next likely question.
- Prefer useful defaults and reversible actions over additional configuration.

### Make motion communicate

- Animate only properties that explain state, hierarchy, or continuity.
- Name the transitioning properties; never use `transition-all` in admin interfaces.
- Use Jaunt motion tokens instead of one-off durations.
- Honor reduced-motion preferences without hiding state changes.

### Practice uncommon care

- Preserve keyboard access, visible focus, semantic structure, and readable contrast.
- Make controls feel consistent before making them distinctive.
- Treat edge cases and empty states as part of the primary experience.
- Prefer calm, durable choices over visual novelty.

## Means

Pilot's approved means remain the existing stack:

- Laravel and Livewire for application behavior.
- Alpine for local interaction state.
- Blade and Flux for view composition.
- Jaunt components and tokens for visual decisions.
- Lucide for interface iconography.

Adopting this philosophy must not replace or bypass those tools, change routes or data contracts, or alter established workflows.

## Method

For every interface change:

1. Identify the user's primary task and preserve it.
2. Remove redundant hierarchy before adding new UI.
3. Build from the nearest Jaunt component and semantic tokens.
4. Add explicit feedback for loading, success, error, and empty states where relevant.
5. Check keyboard, focus, reduced motion, dark mode, and responsive behavior.
6. Compare the rendered result against the reference and run the design-system regression tests.

The standard is not merely that the interface works. It should make the intended action easy to perceive, easy to complete, and easy to trust.
