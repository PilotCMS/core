@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2 text-center">
    <span class="auth-form-eyebrow">Pilot CMS</span>
    <flux:heading size="xl" class="auth-form-title">{{ $title }}</flux:heading>
    <flux:subheading class="auth-form-description">{{ $description }}</flux:subheading>
</div>
