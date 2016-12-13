<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a diagram for survey report template
*/
class ReportTemplateDiagram extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'report_template_diagrams';

    protected $fillable = [];
    public $timestamps = false;

    /**
    * Returns the report template that the diagram belongs to
    */
    public function reportTemplate()
    {
    	return $this->belongsTo('\App\Models\ReportTemplate', 'reportTemplateId');
    }
}
