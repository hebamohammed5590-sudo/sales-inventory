<x-contact-index
    :records="$customers"
    resource="customers"
    title="Customers"
    add-label="Add Customer"
    :model-class="\App\Models\Customer::class"
    :sort="$sort"
/>