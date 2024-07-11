<?php
#Example Format Config
return [
    'return_statement'=> 'return response()->success(\'\', :data);',

    'Modules' => [
        'Area' => [
            'Models' => [
                'City' => [
                    'Fields' => [
                        'name' => ['type' => 'string'],
                        'province_id' => ['type' => 'unsignedBigInteger'],
                        'status' => ['type' => 'boolean', 'options' => ['default' => '1']]
                    ],
                    'Relations' => [
                        'BelongsTo' => [
                            'Area::Province',
                        ],
                    ],
                    'CRUD' => [
                        'Admin' => ['CRUD'],
                        'User' => ['R'],
                    ],
                    'Requests' =>[
                        'admin' => [
                            'store' =>[
                                'name' => ['required' , 'string', 'unique:cities'],
                                'province_id' => ['required', 'exists:provinces,id'],
                                'status' => ['nullable', 'boolean']
                            ],
                            'update' =>[
                                'name' => ['required' , 'string', 'unique:cities,TODO'],
                                'province_id' => ['required', 'exists:provinces,id'],
                                'status' => ['nullable', 'boolean']
                            ]
                        ]

                    ]
                ],
                'Province' => [
                    'Fields' => [
                        'name' => ['type' => 'string'],
                        'status' => ['type' => 'boolean', 'options' => ['default' => '1']]
                    ],
                    'Relations' => [
                        'HasMany' => [
                            'Area::City'
                        ]
                    ],
                    'CRUD' => [
                        'Admin' => ['CRUD'],
                        'User' => ['R'],
                    ],
                    'Requests' =>[
                        'admin' => [
                            'store' =>[
                                'name' => ['required' , 'string', 'unique:provinces'],
                                'status' => ['nullable', 'boolean']
                            ],
                            'update' =>[
                                'name' => ['required' , 'string', 'unique:provinces,TODO'],
                                'status' => ['nullable', 'boolean']
                            ]
                        ]
                    ]
                ],
            ]
        ]
    ],
];
