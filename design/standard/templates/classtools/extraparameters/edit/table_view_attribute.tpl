{*
    @var OCClassExtraParametersHandlerInterface $handler
    @var eZContentClass $class
    @var eZContentClassAttribute $attribute
*}

<h4>Impostazioni visualizzazione tabellare</h4>

<div class="checkbox">
    <label>
        <input type="checkbox" name="extra_handler_{$handler.identifier}[class_attribute][{$class.identifier}][{$attribute.identifier}][show]" value="1" {if $handler.show|contains($attribute.identifier)}checked="checked"{/if} /> Mostra in visualizzazione tabellare
    </label>
</div>

<div class="checkbox">
    <label>
        <input type="checkbox" name="extra_handler_{$handler.identifier}[class_attribute][{$class.identifier}][{$attribute.identifier}][show_title]" value="1" {if $handler.show_title|contains($attribute.identifier)}checked="checked"{/if} /> Mostra etichetta
    </label>
</div>

<div class="checkbox">
    <label>
        <input type="checkbox" name="extra_handler_{$handler.identifier}[class_attribute][{$class.identifier}][{$attribute.identifier}][show_empty]" value="1" {if $handler.show_empty|contains($attribute.identifier)}checked="checked"{/if} /> Mostra anche se non popolato
    </label>
</div>