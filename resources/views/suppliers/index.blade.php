<x-contact-index
    :records="$suppliers"
    resource="suppliers"
    title="Suppliers"
    add-label="Add Supplier"
    :model-class="\App\Models\Supplier::class"
    :sort="$sort"
/>