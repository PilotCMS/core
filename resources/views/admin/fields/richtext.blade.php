<div>
    <flux:field>
        <flux:label>{{ $field['label'] }}</flux:label>
        <flux:textarea
            wire:model.live.debounce.300ms="blocks.{{ array_search($blockId, array_column($blocks, 'id')) }}.data.{{ $field['key'] }}"
            rows="6"
        />
        <flux:description>Supports HTML formatting</flux:description>
    </flux:field>
</div>
