# AMGroups
Accessor Mutator groups for Laravel

## Usage:
Drop AMGroups.php into Larave's Traits folder (in V5.4 = app\Traits )
Include the AMGroups trait into the models you'd like to use Accessor/Mutator Groups

For Accessor/Mutator Groups:
- Create a 2 dimensional array with the variable name $custom_groups in the model. (examples below)
    - The keys of this array will be used as the partial function name (converted to studly casing)
    - The values of those keys will be an array holding the indiviual attribute names to be accessed/mutated with group functions
    - ex: `$custom_groups = [ 'partial_function_name1' => ['attribute1', 'attribute2'] ];`
- Accessors
    - Function name = get + the key in studly case + Accessor
    - The function will receive the single input of the attribute value
    - ex: `protected function getPartialFunctionName1Accessor($value) { return $value + 1; }`
- Mutators
    - Function name = set + the key in studly case + Mutator
    - The function will recieve 2 inputs, the key name followed by the attibute value
    - ex: `protected function setPartialFunctionName1Mutator($key, $value) { $this->attributes[$key] = $value - 1; }`

Additional functions require that the Laravel App has a Cache Driver set up.
- Adds these function on to all models that use this trait:
    - tableColumns($with_primary_key)
        - Cache (if not alreay cached) and returns an array containing all the table attribute names (without the primary key unless true is passed)
    - tableReset()
        - Clears and updates the cached table columns
    - Static function Static()
        - Returns result of a method on a new object as a static result

## Example:
```PHP

namespace App\Models;

// Parent Class
use Illuminate\Database\Eloquent\Model;

// Traits
use App\Traits\AMGroups;

// Time Calculations
use Carbon\Carbon;

class Assignment extends Model {
    use AMGroups;

    protected $table = 'assignment';

    // Accessor/Mutator Groups -Requires AMGroups Trait-
    protected $custom_groups = [
        'c_dates' => [
             'void_date',
             'issue_date',
             'actual_completion_date',
             'date_valid_until',
             'deleted_at'
        ],
        'c_bools' => [
            'ofac'
        ],
        'c_money' => [
            'tariff',
            'accepted_price',
            'miscellaneous',
            'government_fees',
            'admin_fees',
        ],
        'c_id' => [
            'imo',
            'vessel_id'
        ],
        'c_na' => [
            'doc_manager',
            'location',
            'crew_number',
        ],
        'c_share' => [
            'surveyor_share',
            'invoicing_office_share'
        ],
        'c_decimal' => [
            'breadth',
            'gross_tonnage_itc',
            'gross_tonnage_national',
            'dead_weight',
            'length',
        ],
        'c_array' => [
            'secondary_type',
            'provider_type'
        ],
    ];

    /*************
     * Accessors *       --Accessor Group Overrides in AMGroup Trait--
     **************/

    // c_money Group
    private function getCMoneyAccessor($value) {
        return  !is_null($value) ? '$'.$value : null;
    }

    // c_share Group
    public function getCShareAccessor($value) {
        return $value ? $value . '%' : null;
    }

    // c_serialize Group
    public function getCArrayAccessor($value) {
        return unserialize($value);
    }

    /************
     * Mutators *       --Mutator Group Overrides in AMGroup Trait--
     ************/

    // c_dates Group
    private function setCDatesMutator($key, $value) {
        $this->attributes[$key] = $value ? Carbon::parse($value)->toDateString() : NULL;
    }

    // c_serialize Group
    public function setCArrayMutator($key, $value) {
        $this->attributes[$key] = serialize($value);
    }

    // c_decimal
    private function setCDecimalMutator($key, $value) {
        $filtered = $value ? str_replace(',', "", $value) : NULL;
        $this->attributes[$key] = !empty($filtered) ? $filtered : NULL;
    }

    // c_bools Group
    private function setCBoolsMutator($key, $value) {
        $this->attributes[$key] = !empty($value) ? 1 : 0;
    }

    // c_money Group
    private function setCMoneyMutator($key, $value) {
        $filtered = $value ? number_format(str_replace([',','$'], "", $value), 2, '.', '') : NULL;
        $this->attributes[$key] = !empty($filtered) ? $filtered : NULL;
    }

    // c_id group
    private function setCIdMutator($key, $value) {
        $this->attributes[$key] = $value ? ltrim($value, '0') : NULL;
    }

    // c_share group
    private function setCShareMutator($key, $value) {
        $this->attributes[$key] =$value ?  number_format(str_replace('%', '', $value), 2, '.', '') : 0.00;
    }
}
```