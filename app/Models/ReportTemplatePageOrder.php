<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents an order for a page in a report template
*/
class ReportTemplatePageOrder extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'report_template_page_orders';

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
