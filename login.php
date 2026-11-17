<?php require 'inc.php';
if(me()){header('Location: index.php');exit;}
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){chk();$un=trim($_POST['username']??'');
 $s=db()->prepare('SELECT * FROM users WHERE username=?');$s->execute([$un]);$u=$s->fetch();
 if($u&&$u['locked_until']&&strtotime($u['locked_until'])>time())$err='Too many attempts. Try again in 10 minutes.';
 elseif($u&&$u['active']&&password_verify($_POST['password']??'',$u['password_hash'])){
  session_regenerate_id(true);db()->prepare('UPDATE users SET failed=0,locked_until=NULL WHERE id=?')->execute([$u['id']]);
  $_SESSION['u']=['id'=>(int)$u['id'],'name'=>$u['full_name'],'role'=>$u['role']];logit('login');header('Location: index.php');exit;}
 else{if($u){$f=$u['failed']+1;db()->prepare('UPDATE users SET failed=?,locked_until=? WHERE id=?')->execute([$f,$f>=5?date('Y-m-d H:i:s',time()+600):null,$u['id']]);}
  $err='Wrong username or password, or the account is deactivated.';logit('login_failed',$un);}}
head('Log in');?>
<div class="login"><section class="hero-l"><p class="tag">Cavite State University · CEIT</p><h1>Lost something on campus?<br>Someone may have it.</h1><p>Report what you lost, post what you found, and claim your belongings through a verified process.</p></section>
<section class="card"><h2>Log in</h2><?php if($err)echo '<div class="err">'.e($err).'</div>';?>
<form method="post"><?=tok()?><label>Username / student number<input name="username" autocomplete="username" required autofocus></label>
<label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="btn">Log in</button>
<p class="mut">Accounts are created by the administrator.</p></form></section></div>
<?php foot();
