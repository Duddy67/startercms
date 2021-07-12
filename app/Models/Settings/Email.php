<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Mail\AppMailer;
use Illuminate\Support\Facades\Mail;

class Email extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'file_name',
        'subject',
        'body_html',
        'body_text',
    ];


    public function save(array $options = [])
    {
	$this->body_html = preg_replace('#({{.+)-&gt;(.+}})#', '$1->$2', $this->body_html);

        parent::save($options);

	$this->setViewFiles();
    }

    public function delete()
    {
        $code = $this->code;

        parent::delete();

	unlink(resource_path().'/views/emails/'.$code.'.blade.php');
	unlink(resource_path().'/views/emails/'.$code.'_plain.blade.php');
    }

    private function setViewFiles()
    {
	$html = resource_path().'/views/emails/'.$this->code.'.blade.php';
	$text = resource_path().'/views/emails/'.$this->code.'_plain.blade.php';

	file_put_contents($html, $this->body_html);
	file_put_contents($text, $this->body_text);
    }

    public function getItems($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $search = $request->input('search', null);
        $sortedBy = $request->input('sorted_by', null);

	$query = Email::query();

	if ($search !== null) {
	    $query->where('code', 'like', '%'.$search.'%');
	}

	if ($sortedBy !== null) {
	    preg_match('#^([a-z0-9_]+)_(asc|desc)$#', $sortedBy, $matches);
	    $query->orderBy($matches[1], $matches[2]);
	}

        return $query->paginate($perPage);
    }

    public function getFormatOptions()
    {
        return [
	    ['value' => 'plain_text', 'text' => 'Plain text'], 
	    ['value' => 'html', 'text' => 'HTML']
	];
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue($fieldName)
    {
        if ($fieldName == 'format') {
	    return ($this->plain_text) ? 'plain_text' : 'html';
	}
    }

    public static function sendEmail($code, $data)
    {
	$email = Email::where('code', $code)->first();
	$data->subject = self::parseSubject($email->subject, $data);
	// Use the email attribute as recipient in case the recipient attribute doesn't exist.
	$recipient = (!isset($data->recipient) && isset($data->email)) ? $data->email : $data->recipient;
	$data->view = 'emails.'.$code;
	Mail::to($recipient)->send(new AppMailer($data));
    }

    public static function parseSubject($subject, $data)
    {
        if (preg_match_all('#{{\s?\$[a-z0-9_]+\s?}}#U', $subject, $matches)) {
	    $results = $matches[0];
	    $patterns = $replacements = [];

	    foreach ($results as $result) {
		preg_match('#^{{\s?\$([a-zA-Z0-9_]+)\s?}}$#', $result, $matches);
		$attribute  = $matches[1];
		$replacements[] = $data->$attribute;
		$patterns[] = '#({{\s?\$'.$matches[1].'\s?}})#';
	    }

	    return preg_replace($patterns, $replacements, $subject);
	}

	return $subject;
    }
}
