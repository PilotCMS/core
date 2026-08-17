# Perceived Performance

> Every action that must persist is shaped by the reality of how software works. The job is not to make the wait shorter — it is to make the wait *unnoticed*.

## Mask the wait

The best loading state is one the user never sees. If work is unavoidable, either move it into the background or give the user something worth their attention while it happens.

A first-run sequence that explains the product while it indexes data costs the user nothing and buys real seconds. By the time they press the last button, the work is done.

**In Jaunt:** prefer `jaunt.feedback.skeleton` over a spinner whenever the shape of the result is known — a skeleton is itself a form of masking, because it renders the layout before the data exists. Reserve `spinner` for short, shapeless, in-flight actions.

## Write optimistically

Never make a user wait on a round trip you can safely assume will succeed. Apply the change, commit in the background, and handle the rare failure by reverting and offering a retry.

In the Livewire port this is a pattern, not a component: paint the new value in Alpine state immediately, commit with a Livewire action in the background, and on rejection restore the previous value and surface a retry (see `docs/06-interaction-patterns.md`, Optimistic updates). The React reference implementation lives at `resources/design-system/components/feedback/Optimistic.jsx`.

### When not to

Optimism is a promise about likelihood, so it is wrong wherever being wrong is expensive: payments, destructive bulk actions, anything the user must see confirmed before proceeding. There, show real progress and real confirmation.

The same logic scales to flows: rather than trapping someone on a screen awaiting a callback, let them continue and surface a retry if it fails, so they never lose their place.
