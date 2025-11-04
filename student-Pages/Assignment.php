<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="../style/Assignment.css">

</head>

<body>
  <?php
  require("../includes/header.php")
  ?>

  <div class="container">

    <h3>All Assignments</h3>
    <table>
      <tr>
        <th>Title</th>
        <th>Description</th>
        <th>Due Date</th>
        <th>Actions</th>
      </tr>
      <tr>
        <td id="title2"></td>
        <td id="Des2"></td>
        <td id="due-date2"></td>
        <td>
      </tr>

    </table>
  </div>
  <?php
  require("../includes/footer.php")
  ?>
</body>

</html>