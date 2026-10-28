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

    // Symcon's SelectObject/SelectVariable value for Kein(e).
    private const NO_OBJECT = 1;

    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyInteger('ContentVariable', self::NO_OBJECT);
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
        if ($variable !== self::NO_OBJECT && IPS_VariableExists($variable)) {
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
        if ($items[$index]['TargetID'] === self::NO_OBJECT
            || IPS_GetObject((int) $items[$index]['TargetID'])['ObjectType'] !== 3) {
            return;
        }
        $script = $items[$index]['TargetID'];
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
        if (!$builtin && $this->ContentVariableID() === self::NO_OBJECT) {
            $id = $this->RegisterVariableString('Content', $this->Translate('Content'), '~HTMLBox', 0);
            SetValueString($id, $html);
            IPS_SetProperty($this->InstanceID, 'ContentVariable', $id);
        }
        IPS_SetProperty($this->InstanceID, 'HTML', '');
        IPS_ApplyChanges($this->InstanceID);
        return true;
    }

    private function ContentVariableID(): int
    {
        return $this->NormalizeObjectID($this->ReadPropertyInteger('ContentVariable'));
    }

    private function NormalizeObjectID(int $id): int
    {
        // 0 and -1 were stored for an empty selection up to Build 10.
        return $id === 0 || $id === -1 ? self::NO_OBJECT : $id;
    }

    private function Content(): string
    {
        $id = $this->ContentVariableID();
        if ($id !== self::NO_OBJECT && IPS_VariableExists($id) && IPS_GetVariable($id)['VariableType'] === 3) {
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
            $targetID = $this->NormalizeObjectID((int) ($legacyScript ? ($item['ScriptID'] ?? self::NO_OBJECT) : ($item['TargetID'] ?? self::NO_OBJECT)));
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
            return $this->Translate('Gesture sequence: configure at least one gesture.');
        }
        foreach ($gestures as $index => $gesture) {
            if (!in_array($gesture['Gesture'] ?? '', $allowed, true)) {
                return sprintf($this->Translate('Gesture sequence, step %d: unknown gesture.'), $index + 1);
            }
        }
        if (!is_array($items)) {
            return $this->Translate('Menu items: invalid JSON.');
        }
        $variable = $this->ContentVariableID();
        if ($variable !== self::NO_OBJECT && (!IPS_VariableExists($variable) || IPS_GetVariable($variable)['VariableType'] !== 3)) {
            return sprintf($this->Translate('Content: object %d is not an existing string variable.'), $variable);
        }
        foreach ($items as $index => $item) {
            $id = $item['TargetID'];
            // A heading has no action; its caption needs no validation.
            if ($id === self::NO_OBJECT) {
                continue;
            }
            $where = sprintf($this->Translate('Menu item %d (%s): '), $index + 1, $item['Caption'] ?? '');
            if ($id < 0 || !IPS_ObjectExists($id)) {
                return $where . sprintf($this->Translate('target %d does not exist.'), $id);
            }
            if (trim($item['Caption'] ?? '') === '') {
                return $where . $this->Translate('caption for the target is missing.');
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
                    'type' => $item['TargetID'] === self::NO_OBJECT ? 'label' : (IPS_ObjectExists($item['TargetID'])
                        && IPS_GetObject($item['TargetID'])['ObjectType'] === 3 ? 'script' : 'target'),
                    'target' => $item['TargetID']];
            }, $items),
            'tapGap' => max(150, min(1000, $this->ReadPropertyInteger('TapGap'))),
            'holdTime' => max(400, min(3000, $this->ReadPropertyInteger('HoldTime'))),
            'stepTimeout' => max(500, min(10000, $this->ReadPropertyInteger('StepTimeout'))),
            'swipeDistance' => max(20, min(300, $this->ReadPropertyInteger('SwipeDistance'))),
            'texts' => [
                'back' => $this->Translate('Back'),
                'navigationUnavailable' => $this->Translate('Navigation is not available on this client.'),
                'navigationFailed' => $this->Translate('Navigation failed: '),
                'scriptUnavailable' => $this->Translate('Script call is not available on this client.'),
                'scriptFailed' => $this->Translate('Script call failed: '),
                'configFailed' => $this->Translate('Configuration could not be loaded.')
            ]
        ];
    }
}
