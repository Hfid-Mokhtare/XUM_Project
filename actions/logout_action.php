<?php
/**
 * @file logout_action.php
 * @brief Destroys the user's session and logs them out.
 *
 * This script unsets all session variables, destroys the session, and redirects the user
 * to the login page.
 */
session_start();
session_unset();
session_destroy();
header('Location: ../login.php');
exit();
