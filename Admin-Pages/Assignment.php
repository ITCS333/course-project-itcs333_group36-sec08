<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assignment</title>
  <link rel="stylesheet" href="../style/Assignment.css">

</head>

<body>
  <?php
  require('../includes/header.php')
  ?>
  <br>

  <h1 class="header">Assignment pages</h1>
  <br>
  <h2></h2>
  <form method="post" action="">
    <fieldset>
      <legend>Add New Assignment</legend>
      <label for="title">Title:</label>
      <input type="text" id="title" name="title" required>
      <br>
      <label for="Des">Description:</label>
      <textarea id="Des" name="Des" rows="4" cols="50"></textarea>
      <br>
      <label for="due-date">Due Date:</label>

      <input type="date" id="due-date" class="due-date">
      <br>
      <button type="submit">Add Assignment</button>
  </form>
  </fieldset>
  <h2 class="header">All Assignment</h2>
  <br>
  <table border="1px black">
    <tr>
      <th>Title</th>
      <th>Description</th>
      <th>Due Date</th>
      <th></th>

    </tr>
    <tr>
      <td id="title2"></td>
      <td id="Des2"></td>
      <td id="due-date2"></td>
      <td>
        <a href="">Delete</a>
        <a href="">Edit</a>
      </td>
    </tr>
  </table>
  <?php
  require("../includes/footer.php")
  ?>
</body>

</html>