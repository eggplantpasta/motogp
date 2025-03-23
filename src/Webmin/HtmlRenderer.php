<?php

namespace Webmin;

class HtmlRenderer
{
    /**
     * Render an alert message.
     * @param string $message The message to display.
     * @param string $type The type of alert (info, warning, danger, success).
     * @return string The HTML alert.
     */
    public static function renderAlert(string $message, string $type = 'info'): string
    {
        return '<div class="alert alert-' . $type . '"  role="alert">' . htmlspecialchars($message) . '</div>';
    }

    /**
     * Render an HTML table from an array of data.
     *
     * @param array $data The data to render.
     * @return string The HTML table.
     */
    public static function renderTable(array $data): string
    {
        $html = '<table>';
        $html .= '<thead><tr>';
        // Dynamically generate table headers based on the first row's keys
        if (!empty($data)) {
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
        }
        $html .= '</tr></thead><tbody>';

        // Populate table rows with data
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }
    
    /**
     * Render an editable form with a table.
     * 
     * The column names are rendered from the keys of the first row in the data array.
     * The first column is assumed to be the primary key and must be named 'ID'.
     * 
     * An example SQL to get the data to pass to this method might be:
     * 'SELECT rider_id as "ID", name as "Rider Name" FROM riders'.
     *
     * @param string $action The form action URL.
     * @param string $formId The ID of the form.
     * @param string $saveButtonId The ID of the save button.
     * @param array $data The data to populate the table (array of associative arrays).
     * @return string The rendered HTML form.
     */
    public function renderEditableFormWithTable(string $action, string $formId, string $saveButtonId, array $data): string
    {
        $html = '<form method="POST" action="' . htmlspecialchars($action) . '" id="' . htmlspecialchars($formId) . '">';
        $html .= '<button type="submit" disabled id="' . htmlspecialchars($saveButtonId) . '">Save</button>';
        $html .= '<table><thead><tr>';
        // Dynamically generate table headers based on the first row's keys
        if (!empty($data)) {
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
        }
        $html .= '</tr></thead><tbody>';

        // Render table rows
        foreach ($data as $row) {
            $html .= '</tr>';
            $id = "";
            foreach ($row as $key => $cell) {
                if ($key === 'ID') {
                    $id = $cell;
                    // Hidden input for the primary key
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                    $html .= '<input type="hidden" name="data[' . htmlspecialchars($id) . '][id]" value="' . htmlspecialchars($cell) . '">';
                } else {
                    // Editable input for other fields
                    $html .= '<td>';
                    $html .= '<input type="text" name="data[' . htmlspecialchars($id) . '][' . htmlspecialchars($key) . ']" value="' . htmlspecialchars($cell) . '">';
                    $html .= '</td>';
                }
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></form>';

        return $html;
    }

}