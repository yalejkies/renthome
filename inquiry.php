<?php
declare(strict_types=1);
require_once 'config/database.php'; require_once 'includes/auth.php'; require_once 'includes/csrf.php'; require_once 'includes/notifications.php';
require_role('rent_seeker');
$propertyId=filter_input(INPUT_GET,'property_id',FILTER_VALIDATE_INT); if(!$propertyId)exit('Invalid property.');
$stmt=$pdo->prepare("SELECT p.id,p.title,p.price,p.location,p.availability_status,(SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) image_path FROM properties p WHERE p.id=? AND p.status='approved' AND (p.listing_expires_at IS NULL OR p.listing_expires_at > NOW()) AND p.availability_status='available' LIMIT 1"); $stmt->execute([$propertyId]); $property=$stmt->fetch();
if(!$property){http_response_code(404);exit('Property not found.');}
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $message=trim((string)($_POST['message']??''));
    if(!verify_csrf($_POST['csrf_token']??null))$errors[]='Invalid form request. Please try again.';
    if($message===''||strlen($message)<5)$errors[]='Please enter a message.';
    if(strlen($message)>2000)$errors[]='Message is too long.';
    if(!$errors){
        $stmt=$pdo->prepare('INSERT INTO inquiries (property_id,sender_id,message) VALUES (?,?,?)'); $stmt->execute([$propertyId,$_SESSION['user_id'],$message]); $inquiryId=(int)$pdo->lastInsertId();
        $ownerStmt=$pdo->prepare('SELECT owner_id,title FROM properties WHERE id=? LIMIT 1'); $ownerStmt->execute([$propertyId]); $owner=$ownerStmt->fetch();
        if($owner)create_notification($pdo,(int)$owner['owner_id'],'New rental inquiry','Someone sent an inquiry about '.$owner['title'].'.','owner/inquiry.php?id='.$inquiryId);
        header('Location: property.php?id='.$propertyId.'&inquiry=sent'); exit;
    }
}
$pageTitle='Send Inquiry - StayFinder'; require_once 'includes/header.php';
?>
<div class="auth-shell">
<div class="form-card">
    <h1 style="margin-top:0;">Send Inquiry</h1>
    <div class="inquiry-property">
        <?php if(!empty($property['image_path'])):?><img src="<?= property_image_url($property['image_path']) ?>" alt="Property photo"><?php endif; ?>
        <div><h3><?= htmlspecialchars($property['title'],ENT_QUOTES,'UTF-8') ?></h3><div class="price">₱<?= number_format((float)$property['price'],0) ?>/month</div><div class="location-line">⌖ <?= htmlspecialchars($property['location'],ENT_QUOTES,'UTF-8') ?></div></div>
    </div>
    <?php if($errors): ?><div class="alert error"><?php foreach($errors as $error): ?><div><?= htmlspecialchars($error,ENT_QUOTES,'UTF-8') ?></div><?php endforeach; ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="form-group"><label for="message">Your Message</label><textarea id="message" name="message" required maxlength="2000" placeholder="Hi, I'm interested in this property. Is it still available? Could you please provide more details?"></textarea></div>
        <button type="submit" style="width:100%;">Send Inquiry</button>
    </form>
    <a class="secondary-button" style="width:100%;margin-top:10px;" href="property.php?id=<?= (int)$property['id'] ?>">Cancel</a>
</div>
</div>
<?php require_once 'includes/footer.php'; ?>
