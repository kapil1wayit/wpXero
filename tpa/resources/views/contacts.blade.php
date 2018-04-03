<?php 

//echo "<pre>"; print_r($data); echo "</pre>"; 

?>

<!DOCTYPE html>
<html>
<head>
<style>
table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 100%;
}

td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
}

tr:nth-child(even) {
    background-color: #dddddd;
}
</style>
</head>
<body>

<h2>Xero Webhook Response</h2>

<table>
  <tr>
	<th> SR </th>
    <th>name</th>
    <th>phone_number</th>
    <th>Email</th>
  </tr>
  
  <?php $row =1; foreach ($data as $contact) {  ?>
	<tr>
	<td> <?php echo $row; ?> </td>
    <td><?php echo $contact['name']; ?></td>
    <td><?php echo $contact['phone_number']; ?></td>
    <td><?php echo $contact['Email']; ?></td>
  </tr>  
  <?php $row++; } ?>
</table>

</body>
</html>
