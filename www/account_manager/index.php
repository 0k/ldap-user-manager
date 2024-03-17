<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "module_functions.inc.php";
set_page_access("admin");

render_header("$ORGANISATION_NAME account manager");
render_submenu();

$ldap_connection = open_ldap_connection();

if (isset($_POST['delete_user'])) {

  $this_user = $_POST['delete_user'];
  $this_user = urldecode($this_user);

  $del_user = ldap_delete_account($ldap_connection,$this_user);

  if ($del_user) {
    render_alert_banner("User <strong>$this_user</strong> was deleted.");
  }
  else {
    render_alert_banner("User <strong>$this_user</strong> wasn't deleted.  See the logs for more information.","danger",15000);
  }


}

$people = ldap_get_user_list($ldap_connection);

?>
<div class="container">
  <div class="btn" style="float: right;"><?php print count($people);?> account<?php if (count($people) != 1) { print "s"; }?></div>
 <form action="<?php print $THIS_MODULE_PATH; ?>/new_user.php" method="post">
   <button id="add_group" class="btn btn-default" type="submit">New user</button>
 </form> 
 <input class="form-control" id="search_input" type="text" placeholder="Search..">
 <table class="table table-striped">
  <thead>
   <tr>
<?php
          foreach ( $LDAP["default_attribute_map"] as $attribute => $attributeValues ) {
              print "     <th>{$attributeValues["label"]}</th>\n";
          }
print "     <th>Member of</th>\n";
?>
   </tr>
  </thead>
 <tbody id="userlist">
   <script>
    $(document).ready(function(){
      $("#search_input").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#userlist tr").filter(function() {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
      });
    });
  </script>
<?php
foreach ($people as $account_identifier => $attribs){

  $group_membership = ldap_user_group_membership($ldap_connection,$account_identifier);
  if (isset($people[$account_identifier]['mail'])) { $this_mail = $people[$account_identifier]['mail']; } else { $this_mail = ""; }
  print " <tr>\n";
  foreach ( $LDAP["default_attribute_map"] as $attribute => $attributeValues ) {
      print "   <td>";
      switch ($attribute) {
      case "uid":
          print "<a href='{$THIS_MODULE_PATH}/show_user.php?account_identifier=" . urlencode($account_identifier) . "'>$account_identifier</a>";
          break;
      case "mail":
          print "$this_mail\n";
          break;
      case "givenname":
          print $people[$account_identifier]['givenname'];
          break;
      case "sn":
          print $people[$account_identifier]['sn'];
          break;
      default:
          if (isset($people[$account_identifier][$attribute])) {
              $this_value = $people[$account_identifier][$attribute];
          }
          else {
              $this_value = "";
          }
          print "   <td>$this_value</td>\n";
          break;
      }
      print "   </td>";
  }
  print "   <td>" . implode(", ", $group_membership) . "</td>\n";
  print " </tr>\n";

}
?>
  </tbody>
 </table>
</div>
<?php

ldap_close($ldap_connection);
render_footer();
?>
