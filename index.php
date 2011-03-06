<?php
require_once 'Gdata_OAuth_Helper.php';
require_once 'siteUtils.php';
require_once 'siteConfig.php';
require_once 'dbFuncs.php';

session_start();

require_once 'openIdAuth.php';

$APP_URL        = getAppUrl();
$openId         = new Gamut_OpenId();

$consumer       = new Gdata_OAuth_Helper($CONSUMER_KEY, $CONSUMER_SEC);

$dbFuncsObj = new dbFuncs(false);
$dbFuncsObj->doConnect();
/**
 * This switch statement performs selective processing
 * based on the supplied 'action'.
 **/

switch (@$_REQUEST['action']) 
{
    case 'request_token':
        $_SESSION['ACCESS_TOKEN']   = $dbFuncsObj->getAccessToken('mail2naresh@gmail.com');
            
        if (isset($_SESSION['ACCESS_TOKEN']))
        {
            $accessToken        = unserialize($_SESSION['ACCESS_TOKEN']);
            //print $accessToken;
            $httpClient         = $accessToken->getHttpClient($consumer->getOauthOptions());
            $emailService   = new Zend_Gdata_EMail($httpClient);
            $emailId        = $emailService->getEMailFeed();
            print $emailId;
            
            renderHTML('User logged-in with access token: <br> <a href="'. getRedirectUrl().'" >Logout </a>' , false);
        }
        else
        {
            $_SESSION['REQUEST_TOKEN']  = serialize(
                                            $consumer->fetchRequestToken(
                                            implode(' ', $SCOPES), $APP_URL . '?action='.getActionString('access')));
            $consumer->authorizeRequestToken();
        }

        break;

    case 'access_token':

        if (!isset($_SESSION['ACCESS_TOKEN']))
        {    
            $_SESSION['ACCESS_TOKEN']       = serialize($consumer->fetchAccessTokenFromOpenId($_SESSION['REQUEST_TOKEN']));
            $dbFuncsObj->setAccessToken($_SESSION['OPENID_EMAIL'], $_SESSION['ACCESS_TOKEN'], true);
        }
        if ($dbFuncsObj->validateAccessToken($_SESSION['OPENID_EMAIL'], $_SESSION['ACCESS_TOKEN']) == false)
        {
            header('Location: ' . getRedirectUrl());
        }
        else
        {
            global $PYTHON_PATH;
            global $BACKEND_DIR;
            global $BACKEND_BIN;
            //Trigger the backend to download emails.
            /*$command                    = $PYTHON_PATH.' '.$BACKEND_DIR.$BACKEND_BIN.' '.$_SESSION['OPENID_EMAIL'].'&';
            //$command                    = 'rm -rf /home/naresh/LifeParser/Data/GMail_DataStore/mail2naresh@gmail.com';
            exec($command, $output, $retVal);
            print_r ($output);
            $command                    = 'rm -rf /home/naresh/LifeParser/Analysis/mail2naresh@gmail.com*';
            $command                    = 'rm -rf /home/naresh/LifeParser/Data/GMail_DataStore/mail2naresh@gmail.com';
            exec($command, $output, $retVal);*/
            header('Location: ' . constructPageUrl('graphDisplay.php'));
        }
        break;

    case 'logout':
        session_destroy();
        header('Location: ' . $APP_URL);
        exit;

    case 'openid_auth':
        $authorizedToken                = $openId->getRequestToken();
        $_SESSION['REQUEST_TOKEN']      = $authorizedToken;
        $_SESSION['OPENID_EMAIL']       = $openId->getEMailId();
        $_SESSION['OPENID_FIRSTNAME']   = $openId->getFirstName();
        $_SESSION['OPENID_LASTNAME']    = $openId->getLastName();
        header('Location: ' . getRedirectUrl('access'));
        break;

    case 'gadget_login':
        global $openId;
        $redirUrl   = constructPageUrl() . '?action=' . getActionString('openid_auth');
        header('Location: '. $openId->getUrl($redirUrl));
        break;

    case 'login':
    default:
        
        if (!isset($_SESSION['ACCESS_TOKEN'])) 
        {
            renderHTML('login');
        }
        else
        {
            //renderHTML('User: ' . $_SESSION['OPENID_FIRSTNAME'] . ' ' . $_SESSION['OPENID_LASTNAME'] . '<br/> EMail: <b>' . $_SESSION['OPENID_EMAIL'] . '</b> logged-in with access token: <br> <a href="'. getRedirectUrl().'" >Logout </a>' , false);
            renderHTML('flashDisplay');
        }
        break;
}

$dbFuncsObj->doDisconnect();

function renderHTML ($command)
{
?>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link href="default.css" rel="stylesheet" type="text/css">
<title>Life Parser</title>

</head>

<body>
    <table align="center" border="0" height="100%" width="80%"
	bgcolor="#FFFFFF">
            <tr><td class="siteTitle">Life Parser</td></tr>
	    <tr>
                <?php
                    switch ($command)
                    {
                        case 'login':
                        {
                ?>
	        <td>Gmail account is required to use this site.<br></td>
	        <td>
                    <a href="<?php global $openId; $redirUrl   = constructPageUrl() . '?action=' . getActionString('openid_auth');echo $openId->getUrl($redirUrl); ?>">Sign-in with Google Account</a>
		</td>
                <?php
                            break;
                        }

                        case 'flashDisplay':
                        {
                ?>
                <td>
                        <object width="100%" height="100%">
                            <param name="movie" value="LifeParserWeb.swf"/>
                            <embed src="LifeParserWeb.swf" width="100%" height="100%"/>
                        </object>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="<?php echo constructPageUrl().'?action='.getActionString('logout'); ?>">Click Here to Logout.</a>
                </td>
            </tr>
                <?php
                            break;
                        }
                    }
                ?>
    </table>
</body>
</html>
<?php
}
?>
