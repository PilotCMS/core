<div>
    <flux:field>
        <flux:label>{{ $field['label'] }}</flux:label>
        <flux:input
            value="{{ $value ?? '' }}"
            type="text"
            wire:change="updateField('{{ $field['key'] }}', $event.target.value)"
        />
    </flux:field>
</div>
