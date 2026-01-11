<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('type')
                    ->required(),
                TextInput::make('name'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('subject')
                    ->required(),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
            'new' => 'New',
            'in_progress' => 'In progress',
            'replied' => 'Replied',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ])
                    ->default('new')
                    ->required(),
                TextInput::make('language')
                    ->required()
                    ->default('ja'),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Textarea::make('admin_notes')
                    ->columnSpanFull(),
                DateTimePicker::make('replied_at'),
            ]);
    }
}
