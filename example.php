<?php
include("captcha.php");

if (captcha::valid($_POST["captcha"])) {
    echo "Valid";
}
?>

<form action="test.php" method="post">
    <img src="captcha.php?render" />
    <input type="text" name="captcha" />
    <input type="submit" value="Captcha" />
</form>

