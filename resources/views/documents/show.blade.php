<x-layouts::app :title="__($document->original_filename)">
    <livewire:document-detail :document="$document" :key="'doc-'.$document->id" />
</x-layouts::app>