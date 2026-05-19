# UMD Libraries Dynamic Components Module

This Drupal module provides configurable blocks that display UMD Libraries
Design System components. The first configurable block
is the **Person Component Block**, which displays person content using the UMD
Libraries Person web component.

## Features

- **Person Component Block**: Displays Person content with autocomplete search
- **Autocomplete Search**: Search and select Person content by name
- **Field Mapping**: Automatically maps Drupal Person content fields to the
Person component:
  - `title` → `person_name`
  - `field_professional_title` → `person_title`
  - `field_phone` → `person_phone`
  - `field_email` → `person_email`
  - `field_library_department` → `person_department`

## Requirements

- Drupal 10 or higher
- A "Person" content type with the following fields:
  - `title` (built-in)
  - `field_professional_title` (text field)
  - `field_phone` (text field)
  - `field_email` (email field)
  - `field_library_department` (text field or entity reference)

## Installation

1. Clone or copy this module to your Drupal modules directory:

   ```bash
   drupal-modules/umdds_dynamic_components/
   ```

2. Install the module via Drupal UI or Drush:

   ```bash
   drush pm:install umdds_dynamic_components
   ```

## Usage

### Creating a Person Block

1. Go to **Structure > Block Layout** in Drupal
2. Click "Place block" in the region where you want the block
3. Search for and select "UMD Libraries Person Component"
4. Configure the block:
   * Enter a block title (optional)
   * Use the autocomplete field to search and select a Person content item
   * The autocomplete will filter by person title as you type
5. Save the configuration

### Field Mapping

When a Person node is selected, the block automatically extracts and maps the
following fields:

| Drupal Field | Component Property |
|--------------|-------------------|
| title | person_name |
| field_professional_title | person_title |
| field_phone | person_phone |
| field_email | person_email |
| field_library_department | person_department |

Empty fields are omitted from the component output.

## API Endpoints

### Autocomplete Endpoint

* **Route**: `/umdds-dynamic-components/autocomplete/person`
* **Query Parameter**: `q` (search query)
* **Returns**: JSON array of matching Person content items

Example:

```bash
GET /umdds-dynamic-components/autocomplete/person?q=john
```

## Component Integration

The module renders the UMD Libraries Person web component using a Twig template.
The component expects:

```html
<umd-libraries-person
  person-name="John Doe"
  person-title="Librarian"
  person-phone="301-405-0000"
  person-email="john@umd.edu"
  person-department="Reference"
>
</umd-libraries-person>
```

For more information about the UMD Libraries Person component, see:
https://github.com/umd-lib/umdlib-design-system-theme/tree/4.x/components/umd-libraries-person

## Architecture

```
umdds_dynamic_components/
├── src/
│   ├── Plugin/Block/
│   │   └── PersonComponentBlock.php    # Block plugin with form configuration
│   └── Controller/
│       └── AutocompleteController.php  # Autocomplete search handler
├── templates/
│   └── umdds-person-component.html.twig  # Twig template for component rendering
├── umdds_dynamic_components.info.yml   # Module metadata
├── umdds_dynamic_components.module     # Module file with theme hooks
├── umdds_dynamic_components.routing.yml # Route definitions
└── README.md                           # This file
```

## Development Notes

### Adding New Components

To add additional configurable blocks for other UMD Libraries Design System components:

1. Create a new block plugin in `src/Plugin/Block/`
2. Add a new autocomplete controller method for searching relevant content
3. Define routing for the autocomplete endpoint
4. Create a Twig template for rendering the component
5. Register the template in `hook_theme()` in the .module file

### Caching

The block implements proper caching using the `#cache` property with the
selected node's cache tags. This ensures that the block is invalidated whenever
the Person node is updated.

## Troubleshooting

### Block not showing content

* Verify that the Person content type exists
* Check that at least one Person node is published
* Ensure the autocomplete field can find Person content

### Autocomplete not working

* Clear the Drupal cache
* Verify the routing file is properly formatted
* Check that the route is registered with `drush route:rebuild` or `drush cr`

### Component not rendering

* Verify the UMD Libraries Design System JavaScript is loaded on the page
* Check browser console for component errors
* Ensure all required component attributes are set

## License

This module is part of the UMD Libraries Drupal ecosystem.
