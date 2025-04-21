<?php

/**
 * Form Data Processor
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Form;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FormData
 * 
 * Prosesor data formulir
 */
class FormData
{
    /**
     * Form ID
     *
     * @var int
     */
    private $form_id;

    /**
     * Constructor
     *
     * @param int $form_id Form ID
     */
    public function __construct($form_id)
    {
        $this->form_id = $form_id;
    }

    /**
     * Ambil semua field formulir
     *
     * @return array Form fields
     */
    public function get_all_fields()
    {
        // Pastikan Fluent Forms API tersedia
        if (!function_exists('wpFluent')) {
            return [];
        }

        // Ambil struktur formulir
        $form = wpFluent()->table('fluentform_forms')->find($this->form_id);

        if (!$form) {
            return [];
        }

        // Parse form fields dari JSON
        $form_fields = [];
        $form_structure = json_decode($form->form_fields, true);

        // Pengecekan error JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        if (!empty($form_structure['fields'])) {
            $this->extract_fields($form_structure['fields'], $form_fields);
        }

        return $form_fields;
    }

    /**
     * Ambil hanya field telepon
     *
     * @return array Phone fields
     */
    public function get_phone_fields()
    {
        // Ambil semua field terlebih dahulu
        $all_fields = $this->get_all_fields();

        // Filter untuk hanya mengambil field telepon
        $phone_fields = array_filter($all_fields, function ($field) {
            // Daftar kemungkinan tipe field telepon di Fluent Forms
            $phone_field_types = ['phone', 'input_phone', 'phone_number', 'mobile', 'tel'];

            // Jika field element type langsung cocok
            if (in_array($field['type'], $phone_field_types)) {
                return true;
            }

            // Jika field adalah input dengan nama atau label yang mengandung kata kunci telepon
            if ($field['type'] === 'input_text' || $field['type'] === 'input_number') {
                $name_lower = strtolower($field['name']);
                $label_lower = strtolower($field['label']);

                // Periksa jika namanya mengandung kata kunci telepon
                $keywords = ['phone', 'telp', 'telepon', 'hp', 'mobile', 'wa', 'whatsapp', 'nomor'];
                foreach ($keywords as $keyword) {
                    if (strpos($name_lower, $keyword) !== false || strpos($label_lower, $keyword) !== false) {
                        return true;
                    }
                }
            }

            return false;
        });

        return array_values($phone_fields); // Reset array keys
    }

    /**
     * Ekstrak field dari struktur formulir
     *
     * @param array $fields Fields dari struktur formulir
     * @param array $result Array hasil
     * @param string $parent Parent field (opsional)
     * @return void
     */
    private function extract_fields($fields, &$result, $parent = '')
    {
        foreach ($fields as $field) {
            if (!isset($field['element']) || $field['element'] === 'container') {
                if (isset($field['columns'])) {
                    foreach ($field['columns'] as $column) {
                        if (isset($column['fields'])) {
                            $this->extract_fields($column['fields'], $result, $parent);
                        }
                    }
                }
                continue;
            }

            // Skip button elements
            if ($field['element'] === 'button') {
                continue;
            }

            // Get field attributes
            if (isset($field['attributes']['name'])) {
                $name = $field['attributes']['name'];
                $label = isset($field['settings']['label']) ? $field['settings']['label'] : $name;

                if ($parent) {
                    $name = $parent . '.' . $name;
                    $label = $parent . ' - ' . $label;
                }

                $result[] = [
                    'name' => $name,
                    'label' => $label,
                    'type' => $field['element']
                ];
            }
        }
    }

    /**
     * Mendapatkan nilai field dari data formulir
     *
     * @param array $form_data Data formulir
     * @param string $field_name Nama field
     * @return mixed|null Nilai field atau null jika tidak ada
     */
    public function get_field_value($form_data, $field_name)
    {
        if (isset($form_data[$field_name])) {
            $value = $form_data[$field_name];

            // Format nilai jika array
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            return $value;
        }

        return null;
    }

    /**
     * Membangun ringkasan data formulir
     *
     * @param array $form_data Data formulir
     * @param array $included_fields Field yang akan disertakan (atau ['*'] untuk semua)
     * @return string Teks ringkasan data formulir
     */
    public function build_form_data_summary($form_data, $included_fields = ['*'])
    {
        $summary = '';

        foreach ($form_data as $field => $value) {
            // Lewati jika field tidak termasuk dalam daftar dan bukan "semua field" (*) 
            if (!in_array($field, $included_fields) && !in_array('*', $included_fields)) {
                continue;
            }

            // Format nilai jika array
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $summary .= "*{$field}*: {$value}\n";
        }

        return $summary;
    }
}
