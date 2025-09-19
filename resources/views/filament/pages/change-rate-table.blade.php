<x-filament-panels::page>
    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getColumns()"
        :data="$this->getWidgetData()"
    />

    {{ $this->table }}
</x-filament-panels::page>

