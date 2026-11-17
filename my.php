<?php require 'inc.php';need('student');$u=me();
if($_SERVER['REQUEST_METHOD']==='POST'){chk();$n=clean($_POST['name']??'',100);$em=clean($_POST['email']??'',120);
 if($n&&($em===''||filter_var($em,FILTER_VALIDATE_EMAIL))){db()->prepare('UPDATE users SET full_name=?,email=? WHERE id=?')->execute([$n,$em,$u['id']]);$_SESSION['u']['name']=$n;logit('profile_updated');flash('Profile updated.');}else flash('Enter a name and a valid email.');
 if(strlen($_POST['pw']??'')>0){if(strlen($_POST['pw'])>=10){db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($_POST['pw'],PASSWORD_DEFAULT),$u['id']]);logit('password_changed');flash('Profile and password updated.');}else flash('New password needs at least 10 characters.');}
 header('Location: my.php');exit;}
$a=db()->prepare('SELECT * FROM users WHERE id=?');$a->execute([$u['id']]);$me=$a->fetch();
$r=db()->prepare('SELECT i.*,c.name cat FROM items i JOIN categories c ON c.id=i.category_id WHERE i.user_id=? ORDER BY i.created_at DESC');$r->execute([$u['id']]);
$cl=db()->prepare('SELECT cl.*,i.name,i.id iid FROM claims cl JOIN items i ON i.id=cl.item_id WHERE cl.user_id=? ORDER BY cl.created_at DESC');$cl->execute([$u['id']]);
head('My activity');?>
<h1>My activity</h1><div class="cols"><div>
<h2>My reports</h2><table><tr><th>Ticket</th><th>Item</th><th>Status</th><th>Review</th></tr>
<?php foreach($r as $i)echo '<tr><td>'.tno($i['id']).'</td><td>'.e($i['name']).'</td><td>'.badge($i['status']).'</td><td>'.($i['verified']?'Verified':'Waiting').'</td></tr>';?></table>
<h2>My claims</h2><table><tr><th>Item</th><th>Status</th><th>Admin remarks</th></tr>
<?php foreach($cl as $c)echo '<tr><td>'.tno($c['iid']).' '.e($c['name']).'</td><td>'.badge($c['status']).'</td><td>'.e($c['remarks']).'</td></tr>';?></table></div>
<section class="card"><h2>Profile</h2><form method="post"><?=tok()?><label>Student number<input value="<?=e($me['username'])?>" disabled></label>
<label>Full name<input name="name" value="<?=e($me['full_name'])?>" required></label><label>Email<input type="email" name="email" value="<?=e($me['email'])?>"></label>
<label>New password (optional)<input type="password" name="pw" autocomplete="new-password" minlength="10"></label><button class="btn">Save profile</button></form></section></div><?php foot();
