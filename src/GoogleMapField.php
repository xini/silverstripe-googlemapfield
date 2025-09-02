<?php

/**
 * GoogleMapField
 * Lets you record a precise location using latitude/longitude fields to a
 * DataObject. Displays a map using the Google Maps API. The user may then
 * choose where to place the marker; the landing coordinates are then saved.
 * You can also search for locations using the search box, which uses the Google
 * Maps Geocoding API.
 * @author <@willmorgan>
 */

namespace BetterBrief;

use Override;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;

class GoogleMapField extends FormField
{
    protected $data;

    /**
     * @var FieldList
     */
    protected $children;

    /**
     * @var FormField
     */
    protected $latField;

    /**
     * @var FormField
     */
    protected $lngField;

    /**
     * @var FormField
     */
    protected $zoomField;

    /**
     * @var FormField
     */
    protected $boundsField;

    /**
     * The merged version of the default and user specified options
     * @var array
     */
    protected $options = [];

    /**
     * @param DataObject $data The controlling dataobject
     * @param string $title The title of the field
     * @param array $options Various settings for the field
     */
    public function __construct(DataObject $data, $title, $options = [])
    {
        $this->data = $data;

        // Set up fieldnames
        $this->setupOptions($options);

        $this->setupChildren();

        parent::__construct($this->getName(), $title);
    }

    // Auto generate a name
    #[Override]
    public function getName(): string
    {
        $fieldNames = $this->getOption('field_names');
        return sprintf(
            '%s_%s_%s',
            $this->data->class,
            $fieldNames['Latitude'],
            $fieldNames['Longitude']
        );
    }

    /**
     * Merge options preserving the first level of array keys
     * @param array $options
     */
    public function setupOptions(array $options): void
    {
        $this->options = static::config()->default_options;
        foreach ($this->options as $name => &$value) {
            if (isset($options[$name])) {
                $value = is_array($value) ? array_merge($value, $options[$name]) : $options[$name];
            }
        }
    }

    /**
     * Set up child hidden fields, and optionally the search box.
     * @return FieldList the children
     */
    public function setupChildren(): FieldList
    {
        $name = $this->getName();

        // Create the latitude/longitude hidden fields
        $this->latField = HiddenField::create(
            $name . '[Latitude]',
            'Lat',
            $this->recordFieldData('Latitude')
        )->addExtraClass('googlemapfield-latfield no-change-track');

        $this->lngField = HiddenField::create(
            $name . '[Longitude]',
            'Lng',
            $this->recordFieldData('Longitude')
        )->addExtraClass('googlemapfield-lngfield no-change-track');

        $this->zoomField = HiddenField::create(
            $name . '[Zoom]',
            'Zoom',
            $this->recordFieldData('Zoom')
        )->addExtraClass('googlemapfield-zoomfield no-change-track');

        $this->boundsField = HiddenField::create(
            $name . '[Bounds]',
            'Bounds',
            $this->recordFieldData('Bounds')
        )->addExtraClass('googlemapfield-boundsfield no-change-track');
        $this->children = FieldList::create(
            $this->latField,
            $this->lngField,
            $this->zoomField,
            $this->boundsField
        );

        if ($this->options['show_search_box']) {
            $this->children->push(
                TextField::create('Search')
                    ->addExtraClass('googlemapfield-searchfield')
                    ->setAttribute('placeholder', 'Search for a location')
            );
        }

        return $this->children;
    }

    /**
     * @param array $properties
     * @see https://developers.google.com/maps/documentation/javascript/reference
     * {@inheritdoc}
     */
    #[Override]
    public function Field($properties = [])
    {
        $jsOptions = [
            'coords' => [
                $this->recordFieldData('Latitude'),
                $this->recordFieldData('Longitude')
            ],
            'map' => [
                'zoom' => $this->recordFieldData('Zoom') ?: $this->getOption('map.zoom'),
                'mapTypeId' => 'ROADMAP',
            ],
        ];

        $jsOptions = array_replace_recursive($jsOptions, $this->options);
        $this->setAttribute('data-settings', json_encode($jsOptions));
        return parent::Field($properties);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function setValue($record, $data = null): static
    {
        $this->latField->setValue(
            $record['Latitude']
        );
        $this->lngField->setValue(
            $record['Longitude']
        );
        $this->zoomField->setValue(
            $record['Zoom']
        );
        $this->boundsField->setValue(
            $record['Bounds']
        );
        return $this;
    }

    /**
     * Take the latitude/longitude fields and save them to the DataObject.
     * {@inheritdoc}
     */
    #[Override]
    public function saveInto(DataObjectInterface $record): static
    {
        $record->setCastedField($this->childFieldName('Latitude'), $this->latField->dataValue());
        $record->setCastedField($this->childFieldName('Longitude'), $this->lngField->dataValue());
        $record->setCastedField($this->childFieldName('Zoom'), $this->zoomField->dataValue());
        $record->setCastedField($this->childFieldName('Bounds'), $this->boundsField->dataValue());
        return $this;
    }

    /**
     * @return FieldList The Latitude/Longitude fields
     */
    public function getChildFields(): FieldList
    {
        return $this->children;
    }

    protected function childFieldName($name): string
    {
        $fieldNames = $this->getOption('field_names');
        return $fieldNames[$name];
    }

    protected function recordFieldData($name)
    {
        $fieldName = $this->childFieldName($name);
        return $this->data->$fieldName ?: $this->getDefaultValue($name);
    }

    public function getDefaultValue($name)
    {
        $fieldValues = $this->getOption('default_field_values');
        return $fieldValues[$name] ?? null;
    }

    /**
     * @return string The VALUE of the Latitude field
     */
    public function getLatData(): ?string
    {
        $fieldNames = $this->getOption('field_names');
        return $this->data->$fieldNames['Latitude'];
    }

    /**
     * @return string The VALUE of the Longitude field
     */
    public function getLngData(): ?string
    {
        $fieldNames = $this->getOption('field_names');
        return $this->data->$fieldNames['Longitude'];
    }

    /**
     * Get the merged option that was set on __construct
     * @param string $name The name of the option
     * @return mixed
     */
    public function getOption($name)
    {
        // Quicker execution path for "."-free names
        if (!str_contains($name, '.')) {
            if (isset($this->options[$name])) {
                return $this->options[$name];
            }
        } else {
            $names = explode('.', $name);

            $var = $this->options;

            foreach ($names as $n) {
                if (!isset($var[$n])) {
                    return null;
                }

                $var = $var[$n];
            }

            return $var;
        }
        return null;
    }

    /**
     * Set an option for this field
     * @param string $name The name of the option to set
     * @param mixed $val The value of said option
     * @return $this
     */
    public function setOption($name, $val): static
    {
        // Quicker execution path for "."-free names
        if (!str_contains($name, '.')) {
            $this->options[$name] = $val;
        } else {
            $names = explode('.', $name);

            // We still want to do this even if we have strict path checking for legacy code
            $var = &$this->options;

            foreach ($names as $n) {
                $var = &$var[$n];
            }

            $var = $val;
        }

        return $this;
    }
}
