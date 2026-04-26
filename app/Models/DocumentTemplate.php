<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Yaml\Yaml;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'code', 'description', 'file_path', 'format',
        'priority', 'metadata_yaml', 'ui_definition_yaml', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** 6.2.1 applies_when 条件（job_type, role, customer.name_contains 等）をパース。 */
    public function appliesWhen(): array
    {
        if (! $this->metadata_yaml) {
            return [];
        }
        $parsed = Yaml::parse($this->metadata_yaml);
        return $parsed['applies_when'] ?? [];
    }

    /** 6.2.3 チェックボックスUI 定義をパース。 */
    public function uiDefinition(): array
    {
        if (! $this->ui_definition_yaml) {
            return [];
        }
        return Yaml::parse($this->ui_definition_yaml) ?? [];
    }
}
