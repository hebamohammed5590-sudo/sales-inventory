<x-contact-index
    :records="$suppliers"
    resource="suppliers"
    :title="__('Suppliers')"
    :add-label="__('Add Supplier')"
    :model-class="\App\Models\Supplier::class"
    :sort="$sort"
/>