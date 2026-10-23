<?php
/**
 * Partial Tailwind : choix du mode de livraison.
 * Copier dans views/partials/shipping-options.php puis, dans une vue du tunnel de commande :
 *   <?= $viewEngine->partial('partials/shipping-options', ['quotes' => $quotes, 'selected' => $selected ?? null, 'errors' => $errors ?? []]) ?>
 *
 * Variables : $quotes (list<AstralShipping\Models\ShippingQuote>), $selected (?string code), $errors (array)
 * Le formulaire parent doit contenir <?= $csrf->field() ?> et être posté vers ta route de validation.
 */
$quotes   = $quotes ?? [];
$selected = $selected ?? null;
$euro     = static fn(int $cents): string => number_format($cents / 100, 2, ',', ' ') . ' €';
?>
<fieldset class="space-y-3" id="shipping-options">
    <legend class="text-base font-semibold text-gray-900">Mode de livraison</legend>

    <?php if ($quotes === []): ?>
        <p class="text-sm text-red-600">Aucun mode de livraison disponible pour cette adresse.</p>
    <?php endif; ?>

    <?php foreach ($quotes as $quote): ?>
        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4 cursor-pointer hover:border-indigo-500">
            <input type="radio" name="shipping_method" value="<?= htmlspecialchars($quote->code) ?>"
                   class="mt-1" data-requires-pickup="<?= $quote->requiresPickupPoint ? '1' : '0' ?>"
                   <?= $selected === $quote->code ? 'checked' : '' ?> required>
            <span class="flex-1">
                <span class="block font-medium text-gray-900"><?= htmlspecialchars($quote->label) ?></span>
                <?php if ($quote->estimatedDays !== null): ?>
                    <span class="block text-sm text-gray-500">Livraison estimée : <?= (int) $quote->estimatedDays ?> jour(s)</span>
                <?php endif; ?>
            </span>
            <span class="font-semibold text-gray-900"><?= $quote->isFree() ? 'Gratuit' : htmlspecialchars($euro($quote->priceCents)) ?></span>
        </label>
    <?php endforeach; ?>

    <?php /* Rempli par le widget du transporteur (voir docs/widgets-pickup.md) */ ?>
    <input type="hidden" name="pickup_point_id" id="pickup_point_id" value="">
    <input type="hidden" name="pickup_point_label" id="pickup_point_label" value="">
    <div id="pickup-widget" class="hidden"></div>

    <?php if (!empty($errors['shipping_method'])): ?>
        <p class="text-sm text-red-600"><?= htmlspecialchars((string) $errors['shipping_method']) ?></p>
    <?php endif; ?>
</fieldset>
