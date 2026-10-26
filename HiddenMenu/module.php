<?php

class HiddenMenu extends IPSModule
{
    // MD5 of the HTML shipped as default up to 1.0 Build 9 (trimmed, LF line endings).
    private const BUILTIN_HTML = [
        '1347599d2214576805a417262de30bfe',
        '04b377d1871b621529e477ecdb3ab70a',
        '827f507a1c5ea2f679e6042d0442f2bb',
        '6d87887652620ae91865318f50103ee0'
    ];

    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyInteger('ContentVariable', 0);
        $this->RegisterPropertyInteger('Background', -1);
        $this->RegisterPropertyString('Gesture', '[{"Gesture":"tap3"}]');
        $this->RegisterPropertyString('Items', '[]');
        $this->RegisterPropertyInteger('TapGap', 600);
        $this->RegisterPropertyInteger('HoldTime', 800);
        $this->RegisterPropertyInteger('StepTimeout', 2500);
        $this->RegisterPropertyInteger('SwipeDistance', 60);
        // Up to 1.0 Build 9 only; ApplyChanges moves a custom value into a child variable.
        $this->RegisterPropertyString('HTML', '');
        $this->SetVisualizationType($this->VisualizationType());
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
        $this->SetVisualizationType($this->VisualizationType());
        foreach ($this->GetMessageList() as $sender => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($sender, $message);
            }
        }
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
        } elseif ($this->MigrateHTML()) {
            return;
        }
        $variable = $this->ContentVariableID();
        if ($variable !== 0 && IPS_VariableExists($variable)) {
            $this->RegisterMessage($variable, VM_UPDATE);
        }
        $error = $this->ConfigurationError();
        $this->SetStatus($error === '' ? 102 : 200);
        $this->UpdateFormField('ValidationMessage', 'caption', $error);
        $this->UpdateFormField('ValidationMessage', 'visible', $error !== '');
        if ($error !== '') {
            $this->SendDebug('ConfigurationError', $error, 0);
        }
        $this->UpdateVisualizationValue(json_encode($this->ClientConfiguration()));
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        if ($Message === IPS_KERNELSTARTED) {
            $this->ApplyChanges();
            return;
        }
        if ($Message === VM_UPDATE && $SenderID === $this->ContentVariableID()) {
            // Content only: the client keeps its current view and gesture progress.
            $this->UpdateVisualizationValue(json_encode(['content' => $this->Content()]));
        }
    }

    public function GetVisualizationTile()
    {
        $config = json_encode($this->ClientConfiguration(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        // strtr replaces in a single pass: placeholders inside the displayed HTML stay untouched.
        return strtr(file_get_contents(__DIR__ . '/module.html'), [
            '/*__CONFIG__*/{}' => $config,
            '/*__ENGINE__*/' => file_get_contents(__DIR__ . '/gestures.js'),
            '/*__INPUT__*/' => file_get_contents(__DIR__ . '/input.js'),
            '/*__LAYOUT__*/' => file_get_contents(__DIR__ . '/layout.js'),
            '/*__PRESENTATION__*/' => file_get_contents(__DIR__ . '/presentation.js')
        ]);
    }

    public function RequestAction($Ident, $Value)
    {
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

    private function VisualizationType(): int
    {
        // Fullscreen HTML tiles (type 2) exist from 9.1; older kernels use the normal HTML SDK (type 1).
        return version_compare(IPS_GetKernelVersion(), '9.1', '>=') ? 2 : 1;
    }

    private function MigrateHTML(): bool
    {
        $html = $this->ReadPropertyString('HTML');
        if ($html === '') {
            return false;
        }
        $builtin = in_array(md5(trim(str_replace("\r\n", "\n", $html))), self::BUILTIN_HTML, true);
        // A custom page becomes the content variable; a shipped page is replaced by the current one.
        if (!$builtin && $this->ContentVariableID() === 0) {
            $id = $this->RegisterVariableString('Content', 'Inhalt', '~HTMLBox', 0);
            SetValueString($id, $html);
            IPS_SetProperty($this->InstanceID, 'ContentVariable', $id);
        }
        IPS_SetProperty($this->InstanceID, 'HTML', '');
        IPS_ApplyChanges($this->InstanceID);
        return true;
    }

    private function ContentVariableID(): int
    {
        $id = $this->ReadPropertyInteger('ContentVariable');
        // SelectVariable displays its default ID 1 as Kein(e).
        return $id === -1 || $id === 1 ? 0 : $id;
    }

    private function Content(): string
    {
        $id = $this->ContentVariableID();
        if ($id !== 0 && IPS_VariableExists($id) && IPS_GetVariable($id)['VariableType'] === 3) {
            return GetValueString($id);
        }
        return file_get_contents(__DIR__ . '/default.html');
    }

    private function MenuItems(): ?array
    {
        $items = json_decode($this->ReadPropertyString('Items'), true);
        if (!is_array($items)) {
            return null;
        }
        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
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
                'Background' => (int) ($item['Background'] ?? -1)
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
        $variable = $this->ContentVariableID();
        if ($variable !== 0 && (!IPS_VariableExists($variable) || IPS_GetVariable($variable)['VariableType'] !== 3)) {
            return 'Inhalt: Objekt ' . $variable . ' ist keine vorhandene String-Variable.';
        }
        foreach ($items as $index => $item) {
            $id = (int) ($item['TargetID'] ?? 0);
            // A heading has no action; its caption needs no validation.
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
        }
        return '';
    }

    private function ClientConfiguration(): array
    {
        $items = $this->MenuItems() ?? [];
        return [
            'valid' => $this->ValidConfiguration(),
            'content' => $this->Content(),
            'background' => $this->ReadPropertyInteger('Background'),
            'gesture' => array_column(json_decode($this->ReadPropertyString('Gesture'), true) ?: [], 'Gesture'),
            'items' => array_map(function ($item) {
                return ['caption' => $item['Caption'] ?? '',
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
