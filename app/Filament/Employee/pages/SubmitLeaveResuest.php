<?php

namespace App\Filament\Employee\Pages;

use BackedEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SubmitLeaveResuest extends Page implements HasForms {

    use InteractsWithForms;

    protected string $view = 'filament.employee.pages.submit-leave-resuest';
    protected static BackedEnum|string|null $navigationIcon = Heroicon::DocumentDuplicate;
    protected static ?int $navigationSort = 2;

    public ?array $data = [];
    public $form_name;
    public $form_date;
    public $form_id;
    public $form_slug;
    public $hideForm = false;
    public $showQrCode = false;
    
    public function mount(): void {
        $this->form->fill();
        $this->form_id = request('form_id');
    }

    public function form(Schema $schema): Schema {
        return $schema
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->maxLength(70)
                            ->minLength(3)
                            ->required(),
                        TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->maxLength(12)
                            ->minLength(5)
                            ->tel()
                            ->prefix('+964')
                            ->required(),
                        Select::make('registered_as')
                            ->label('Registered As')
                            ->options([
                                'Individual' => 'Individual',
                                'Company' => 'Company',
                                'University' => 'University',
                                'NGO' => 'NGO',
                                'Government' => 'Government',
                                'Freelancer / Self-Employed' => 'Freelancer / Self-Employed',
                                'Student' => 'Student'
                            ])
                            ->native(false)
                            ->required()
                            ->placeholder('Select One'),
                        TextInput::make('job_title')
                            ->label('Job Title')
                            ->maxLength(70)
                            ->minLength(3),
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(70)
                            ->minLength(3),
                        TextInput::make('country')
                            ->label('Country')
                            ->default('Iraq')
                            ->disabled()
                            ->maxLength(70)
                            ->minLength(3)
                            ->required(),
                        Select::make('city')
                            ->label('City')
                            ->options(config('city'))
                            ->searchable()
                            ->default('Erbil')
                            ->native(false)
                            ->required()
                            ->placeholder('Select One'),
                        TextInput::make('address')
                            ->label('Address')
                            ->maxLength(70)
                            ->minLength(3)
                            ->required(),
                        DatePicker::make('date_of_birth')
                            ->label('Date of Birth')
                            ->nullable()
                            ->placeholder('Select Date')
                            ->displayFormat('d-m-Y')
                            ->native(false),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->autocomplete('email')
                            ->maxLength(70)
                            ->minLength(3)
                            ->required()
                            ->unique(
                                table: 'registered_forms',
                                column: 'email',
                                modifyRuleUsing: function ($rule) {
                                    return $rule->where('form_id', $this->form_id);
                                }
                            )
                    ]),
                Checkbox::make('create_account')
                    ->label('Create an account?')
                    ->reactive()
                    ->default(true),
                Grid::make(2)
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->maxLength(70)
                            ->minLength(8)
                            ->required()
                            ->reactive()
                            ->revealable()
                            ->visible(fn (callable $get) => $get('create_account'))
                    ])
            ])
            ->statePath('data');
    }

    public function submit(): void {

        // Validate the form data
        $this->validate();

        // Extract the form data

        // $form_id = $this->form_id;
        // $name = $this->data['name'];
        // $phone_number = $this->data['phone_number'];
        // $date_of_birth = date('Y-m-d', strtotime($this->data['date_of_birth']));
        // $registered_as = $this->data['registered_as'];
        // $job_title = $this->data['job_title'];
        // $company_name = $this->data['company_name'];
        // $country = 'Iraq';
        // $city = $this->data['city'];
        // $address = $this->data['address'];
        // $email = $this->data['email'];
        // $create_account = $this->data['create_account'];

        // // Create account if the checkbox is checked and the email is not already registered
        // if ($create_account && !User::where('email', $email)->exists()){
        //     $password = $this->data['password'];
        //     $user = User::withoutEvents(function () use ($name, $email, $phone_number, $password) {
        //         $user = new User();
        //         $user->name = $name;
        //         $user->type = 'retail';
        //         $user->email = $email;
        //         $user->phone_number = $phone_number;
        //         $user->password = bcrypt($password);
        //         $user->save();
        //         return $user;
        //     });
        //     if ($user) {
        //         $subEmail = Email::where('email', $user->email)->first();
        //         $verify_token = hash('sha256', Str::random(60));
        //         if (!$subEmail){
        //             $subscribe_token = hash('sha256', Str::random(60));
        //             $hasEmail = new Email();
        //             $hasEmail->email = $user->email;
        //             $hasEmail->token = $subscribe_token;
        //             $hasEmail->created_at = now();
        //             $hasEmail->save();
        //             DB::table('email_verify_tokens')->insert([
        //                 'email' => $user->email,
        //                 'token' => $verify_token,
        //                 'created_at' => now()
        //             ]);
        //             Mail::mailer('no_reply')->to($user->email)->send(new WelcomeMail(
        //                 $user->name, $verify_token, $subscribe_token
        //             ));
        //         }else{
        //             if ($subEmail->is_subscribed){
        //                 $evt = DB::table('email_verify_tokens')->where('email', $user->email)->first();
        //                 if(!$evt){
        //                     DB::table('email_verify_tokens')->insert([
        //                         'email' => $user->email,
        //                         'token' => $verify_token
        //                     ]);
        //                 }else{
        //                     $verify_token = $evt->token;
        //                 }
        //                 Mail::mailer('no_reply')->to($user->email)->send(new WelcomeMail(
        //                     $user->name, $verify_token, $subEmail->token
        //                 ));
        //             }
        //         }
        //     }
        // }

        // Insert the form data into the database
        // $registeredForm = new RegisteredForm();
        // $registeredForm->form_id = $form_id;
        // $registeredForm->name = $name;
        // $registeredForm->phone_number = $phone_number;
        // $registeredForm->date_of_birth = $date_of_birth;
        // $registeredForm->registered_as = $registered_as;
        // $registeredForm->job_title = $job_title;
        // $registeredForm->company_name = $company_name;
        // $registeredForm->country = $country;
        // $registeredForm->city = $city;
        // $registeredForm->address = $address;
        // $registeredForm->email = $email;

        // if ($registeredForm->save()) {
        //     if (app()->isProduction()) {
        //         $generatedQrCode = QrCode::format('png')->size(200)->generate(url('/form/check-in/'.$registeredForm->id));
        //     }else{
        //         $generatedQrCode = url('/form/check-in/'.$registeredForm->id);
        //     }
        //     $qrCode = base64_encode($generatedQrCode);
        //     $this->hideForm = true;
        //     $this->showQrCode = $qrCode;
        //     Notification::make()
        //         ->title('Form Submitted Successfully.')
        //         ->success()
        //         ->send();
        // } else {
        //     Notification::make()
        //         ->title('Form Submission Failed.')
        //         ->danger()
        //         ->send();
        // }

    }

}