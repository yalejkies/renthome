<?php
$ownerStep = isset($ownerStep) ? (int)$ownerStep : 1;
$ownerSteps = [
    1 => ['Register', 'register.php'],
    2 => ['Policies', 'policies.php'],
    3 => ["Mayor's Permit", 'permit.php'],
    4 => ['Create Listing', 'create-listing.php'],
    5 => ['Submit', 'review-listing.php'],
    6 => ['Admin Review', 'dashboard.php'],
    7 => ['Approved', 'dashboard.php'],
    8 => ['Public Listing', 'dashboard.php'],
];
?>
<div class="owner-flow" aria-label="Owner listing process">
    <?php foreach ($ownerSteps as $stepNo => [$label, $href]): ?>
        <div class="owner-flow-step <?= $stepNo === $ownerStep ? 'active' : ($stepNo < $ownerStep ? 'done' : '') ?>">
            <span class="owner-flow-number"><?= $stepNo < $ownerStep ? '✓' : $stepNo ?></span>
            <span class="owner-flow-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php if ($stepNo < count($ownerSteps)): ?><span class="owner-flow-arrow">→</span><?php endif; ?>
    <?php endforeach; ?>
</div>
