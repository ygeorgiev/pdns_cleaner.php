<?php
/* config ***********************************************************************************************************************/
$my_dns = array("ns1.bashost.org.","ns2.bashost.org.");	// add your DNS servers (Don't forget to add а dot after the server name)
$debug = 1;						// 1 - show domain status; 0 - delete domains
$conf = parse_ini_file('/etc/powerdns/pdns.conf');	// get mysql configuration from poweerdns config file
error_reporting(E_ALL & ~E_DEPRECATED);			// small php "hack"
/* end config *******************************************************************************************************************/

/* connet to the database*/
$link = mysql_connect($conf['gmysql-host'], $conf['gmysql-user'], $conf['gmysql-password']);
/* select database */
mysql_select_db($conf['gmysql-dbname']);
/* get all domains and exclude revers DNS zones */
$query = "select name from domains where name NOT LIKE '%arpa'";
$result = mysql_query($query);

/* go go go */
while ($row = mysql_fetch_assoc($result))
{
  /* check DNS server for domain */
  if (dns_match($my_dns,$row["name"]) === TRUE)
  {
    if($debug==1)
    {
      echo $row["name"]." use my dns\n";
    }
  }
  else
  {
    if($debug==1)
    {
      echo $row["name"]." don't use my dns\n";
    }
    else
    {
      $sql = "SELECT id FROM domains WHERE name='".$row["name"]."' LIMIT 1";	// get domain ID in domains table
      $result = mysql_query($sql);
      $r = mysql_fetch_row($result);
      $query = "DELETE FROM domains WHERE name='".$r[0]."'";			// delete from domains
      mysql_query($sql);
      $query = "DELETE FROM records WHERE name='".$r[0]."'";			// delete from records
      mysql_query($sql);
    }
  }
}


function dns_match($my_dns,$domain)
{
  /* get DNS servers */
  $command = 'dig +short -tns '.$domain;
  exec($command,$dig);
  if ( count($dig)== 0 ) return FALSE; /* don't have DNS or doesn't work; My DNS works file ;) */
  /* match hack !? */
  $dig_uper = array_change_key_case($dig);
  $all_dns = array_unique(array_merge($my_dns,$dig_uper));
  $c1 = count($my_dns) + count($dig_uper);
  $c2 = count($all_dns);
  if ($c1 == $c2)
  {
    return FALSE;
  }
  else
  {
    return TRUE;
  }
}
?>
