<?php

require_once 'bootstrap.php';

include 'partials/page_top.html';

use Webmin\Database;
use Webmin\HtmlRenderer;

try {
    // Initialize the Database class with the SQLite DSN
    $db = new Database(SQLITE_DSN);

    // Check if the form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

// echo '<pre>';
// print_r($_POST);
// echo '</pre>';

        // Loop through the submitted data and update each rider
        $count_updated = 0;
        foreach ($_POST['data'] as $rider) {
            $id = $rider['id'];
            $name = $rider['Rider Name'];

            $sql = 'UPDATE riders SET name = :name WHERE rider_id = :id and name <> :name';
            $affectedRows = $db->execute($sql, [':name' => $name, ':id' => $id]);

            $count_updated += $affectedRows;
          }
          $renderer = new HtmlRenderer();
          switch ($count_updated) {
            case 0:
                echo $renderer->renderAlert('No riders were updated.', 'info');
                break;
            case 1:
                echo $renderer->renderAlert("1 rider updated.", 'success');;
                break;
            default:
                echo $renderer->renderAlert("$count_updated riders updated.", 'success');
        }
    }

    // Fetch all riders from the database
    $sql = 'SELECT rider_id as "ID", name as "Rider Name", active as "Active" FROM riders';
    $riders = $db->query($sql);

    // Render the form using HtmlRenderer
    $renderer = new HtmlRenderer();
    echo $renderer->renderEditableFormWithTable(
        'edit-riders.php', // Form action
        'editRidersForm',  // Form ID
        'saveButton',      // Save button ID
        $riders,           // Data
    );


} catch (\PDOException $e) {
    // Handle any errors
    echo "Error: " . $e->getMessage();
}

include 'partials/page_bottom.html';
?>

<script>
// Call the function for the current form
document.addEventListener('DOMContentLoaded', function () {
    enableSaveButtonOnInput('editRidersForm', 'saveButton');
});
</script>