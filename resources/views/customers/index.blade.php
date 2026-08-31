<x-contact-index
    :records="$customers"
    resource="customers"
    :title="__('Customers')"
    :add-label="__('Add Customer')"
    :model-class="\App\Models\Customer::class"
    :sort="$sort"
/>