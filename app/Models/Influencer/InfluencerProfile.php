<?php

namespace App\Models\Influencer;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class InfluencerProfile extends BaseModel {
    use HasOrganization;
protected $table = "cmis.influencer_profiles";
    protected $primaryKey = "influencer_id";
    protected $fillable = ["org_id","added_by","full_name","email","phone","bio","profile_image","location","languages","social_accounts","total_followers","avg_engagement_rate","niches","content_types","tier","audience_demographics","audience_quality_score","authenticity_score","reliability_score","completed_campaigns","total_campaigns","avg_roi","rates","available_for_partnerships","preferred_collaboration_type","exclusive_partnership","blacklisted_brands","preferred_brands","status","tags","internal_notes","source","last_contacted_at","last_campaign_at"];
    protected $casts = ["languages"=>"array","social_accounts"=>"array","niches"=>"array","content_types"=>"array","audience_demographics"=>"array","audience_quality_score"=>"array","rates"=>"array","available_for_partnerships"=>"boolean","exclusive_partnership"=>"boolean","blacklisted_brands"=>"array","preferred_brands"=>"array","tags"=>"array","last_contacted_at"=>"datetime","last_campaign_at"=>"datetime"];
    public function partnerships():HasMany{return $this->hasMany(InfluencerPartnership::class,"influencer_id","influencer_id");}
    public function activate():void{$this->update(["status"=>"active"]);}
    public function calculateTier():void{$f=$this->total_followers;$this->update(["tier"=>match(true){$f<10000=>"nano",$f<100000=>"micro",$f<500000=>"mid",$f<1000000=>"macro",default=>"mega"}]);}
    public function scopeActive($q): Builder{return $q->where("status","active");}
    public function scopeByTier($q,$tier): Builder{return $q->where("tier",$tier);}
}