<?php

class HiddenMenu extends IPSModule
{
    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString('HTML', file_get_contents(__DIR__ . '/default.html'));
        $this->RegisterPropertyInteger('Background', -1);
        $this->RegisterPropertyString('CloseCaption', 'Schliessen');
        $this->RegisterPropertyInteger('CloseTarget', 0);
        $this->RegisterPropertyString('Gesture', '[{"Gesture":"tap3"}]');
        $this->RegisterPropertyString('Items', '[]');
        $this->RegisterPropertyInteger('TapGap', 600);
        $this->RegisterPropertyInteger('HoldTime', 800);
        $this->RegisterPropertyInteger('StepTimeout', 2500);
        $this->RegisterPropertyInteger('SwipeDistance', 60);
        $this->SetVisualizationType(defined('INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN') ? constant('INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN') : 1);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
        $this->SetVisualizationType(defined('INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN') ? constant('INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN') : 1);
        $error = $this->ConfigurationError();
        $this->SetStatus($error === '' ? 102 : 200);
        $this->UpdateFormField('ValidationMessage', 'caption', $error);
        $this->UpdateFormField('ValidationMessage', 'visible', $error !== '');
        if ($error !== '') {
            $this->SendDebug('ConfigurationError', $error, 0);
        }
        $this->UpdateVisualizationValue(json_encode($this->ClientConfiguration()));
    }

    public function GetVisualizationTile()
    {
        $config = json_encode($this->ClientConfiguration(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        return str_replace(['/*__CONFIG__*/{}', '/*__ENGINE__*/', '/*__INPUT__*/', '/*__LAYOUT__*/', '/*__PRESENTATION__*/'], [$config, file_get_contents(__DIR__ . '/gestures.js'), file_get_contents(__DIR__ . '/input.js'), file_get_contents(__DIR__ . '/layout.js'), file_get_contents(__DIR__ . '/presentation.js')], file_get_contents(__DIR__ . '/module.html'));
    }

    public function RequestAction($Ident, $Value)
    {
        if ($Ident === 'ResetHTML') {
            IPS_SetProperty($this->InstanceID, 'HTML', file_get_contents(__DIR__ . '/default.html'));
            IPS_ApplyChanges($this->InstanceID);
            $this->ReloadForm();
            return;
        }
        if ($Ident !== 'Execute' || !$this->ValidConfiguration()) {
            return;
        }
        if (!is_int($Value) && !(is_string($Value) && ctype_digit($Value))) {
            return;
        }
        $items = $this->MenuItems();
        $index = (int) $Value;
        if (!isset($items[$index])) {
            return;
        }
        if ((int) $items[$index]['TargetID'] <= 0
            || IPS_GetObject((int) $items[$index]['TargetID'])['ObjectType'] !== 3) {
            return;
        }
        $script = (int) $items[$index]['TargetID'];
        // Only configured scripts are accepted; the client never supplies a script ID.
        IPS_RunScriptEx($script, ['HIDDM_INSTANCE' => $this->InstanceID, 'HIDDM_ITEM' => $index]);
    }

    public function GetConfigurationForm()
    {
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        foreach ($form['elements'] as &$element) {
            if (($element['name'] ?? '') === 'ValidationMessage') {
                $error = $this->ConfigurationError();
                $element['caption'] = $error;
                $element['visible'] = $error !== '';
            }
            if (($element['name'] ?? '') === 'Items') {
                $element['values'] = $this->MenuItems() ?? [];
            }
        }
        unset($element);
        return json_encode($form);
    }

    private function MenuItems(): ?array
    {
        $items = json_decode($this->ReadPropertyString('Items'), true);
        if (!is_array($items)) {
            return null;
        }
        $result = [];
        foreach ($items as $item) {
            // Read Build 1-3 entries without changing stored properties during rendering.
            $legacyScript = !array_key_exists('TargetID', $item) && array_key_exists('ScriptID', $item);
            $targetID = (int) ($legacyScript ? ($item['ScriptID'] ?? 0) : ($item['TargetID'] ?? 0));
            // SelectObject displays its default ID 1 as Kein(e).
            if ($targetID === -1 || $targetID === 1) {
                $targetID = 0;
            }
            $result[] = [
                'Caption' => $item['Caption'] ?? '',
                'TargetID' => $targetID,
                'Icon' => trim((string) ($item['Icon'] ?? '')),
                'Background' => (int) ($item['Background'] ?? -1),
                'After' => $item['After'] ?? 'menu'
            ];
        }
        return $result;
    }

    private function ValidConfiguration(): bool
    {
        return $this->ConfigurationError() === '';
    }

    private function ConfigurationError(): string
    {
        $gestures = json_decode($this->ReadPropertyString('Gesture'), true);
        $items = $this->MenuItems();
        $allowed = ['tap1', 'tap2', 'tap3', 'tap4', 'tap5', 'hold', 'up', 'down', 'left', 'right', 'upLeft', 'upRight', 'downLeft', 'downRight'];
        if (!is_array($gestures) || count($gestures) < 1) {
            return 'Gestenfolge: Mindestens eine Geste konfigurieren.';
        }
        foreach ($gestures as $index => $gesture) {
            if (!in_array($gesture['Gesture'] ?? '', $allowed, true)) {
                return 'Gestenfolge, Schritt ' . ($index + 1) . ': Unbekannte Geste.';
            }
        }
        if (!is_array($items)) {
            return 'Menüeinträge: Ungültiges JSON.';
        }
        $target = max(0, $this->ReadPropertyInteger('CloseTarget'));
        if ($target !== 0 && (!IPS_ObjectExists($target) || IPS_GetObject($target)['ObjectType'] !== 0)) {
            return 'Schliessen-Ziel: Objekt ' . $target . ' ist keine vorhandene Kategorie.';
        }
        foreach ($items as $index => $item) {
            $id = (int) ($item['TargetID'] ?? 0);
            // A heading has no action; neither its caption nor After needs validation.
            if ($id === 0) {
                continue;
            }
            $where = 'Menüeintrag ' . ($index + 1) . ' (' . ($item['Caption'] ?? '') . '): ';
            if ($id < 0 || !IPS_ObjectExists($id)) {
                return $where . 'Target ' . $id . ' existiert nicht.';
            }
            if (trim($item['Caption'] ?? '') === '') {
                return $where . 'Beschriftung für das Target fehlt.';
            }
            if (IPS_GetObject($id)['ObjectType'] === 3) {
                if (!in_array($item['After'] ?? '', ['menu', 'html', 'close'], true)) {
                    return $where . 'Ungültige Ansicht nach Targetaufruf.';
                }
                if ($item['After'] === 'close' && $target === 0) {
                    return $where . 'Ansicht Schliessen-Ziel gewählt, aber kein Schliessen-Ziel konfiguriert.';
                }
            }
        }
        return '';
    }

    private function ClientConfiguration(): array
    {
        $items = $this->MenuItems() ?? [];
        return [
            'valid' => $this->ValidConfiguration(),
            'html' => $this->ReadPropertyString('HTML'),
            'background' => $this->ReadPropertyInteger('Background'),
            'closeCaption' => $this->ReadPropertyString('CloseCaption'),
            'closeTarget' => max(0, $this->ReadPropertyInteger('CloseTarget')),
            'gesture' => array_column(json_decode($this->ReadPropertyString('Gesture'), true) ?: [], 'Gesture'),
            'items' => array_map(function ($item) {
                return ['caption' => $item['Caption'] ?? '', 'after' => $item['After'] ?? 'menu',
                    'icon' => $item['Icon'] ?? '', 'background' => (int) ($item['Background'] ?? -1),
                    'type' => (int) ($item['TargetID'] ?? 0) === 0 ? 'label' : (IPS_ObjectExists((int) ($item['TargetID'] ?? 0))
                        && IPS_GetObject((int) $item['TargetID'])['ObjectType'] === 3 ? 'script' : 'target'),
                    'target' => (int) ($item['TargetID'] ?? 0)];
            }, $items),
            'tapGap' => max(150, min(1000, $this->ReadPropertyInteger('TapGap'))),
            'holdTime' => max(400, min(3000, $this->ReadPropertyInteger('HoldTime'))),
            'stepTimeout' => max(500, min(10000, $this->ReadPropertyInteger('StepTimeout'))),
            'swipeDistance' => max(20, min(300, $this->ReadPropertyInteger('SwipeDistance')))
        ];
    }
}
