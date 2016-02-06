{
	"radius": 35,
	"offset": 0,
	"path": [
		[700, 0],
		[{
				"p1": 0.33,
				"p2": 0.67,
				"start": [700, 297]
			},
			[],
			[
				[{
						"p1": 0.5,
						"p2": 0.5,
						"start": [700, 466]
					},
					[],
					[
						[700, 620],
						[90, 620]
					]
				],
				[90, 466]
			]
		],
		[90, 297],
		[90, 105]
	],
	"baseRadius": 35,
	"baseLocation": [90, 105],
	"startingBaseHealth": 100,
	"maxBaseHealth": 100,
	"startingResources": 150,
	"waves": [{
		"0": {
			"tankBasic": 5,
			"delay": 3
		},
		"20": {
			"tankBasic": 10,
			"delay": 2
		},
		"30": {
			"scoutDroid": 10,
			"delay": 2
		}
	}, {
		"0": {
			"scoutDroid": 10,
			"delay": 2
		},
		"15": {
			"lightFighter": 5,
			"delay": 2
		},
		"25": {
			"heavyFighter": 3,
			"delay": 5
		}
	}, {
		"0": {
			"lightFighter": 10,
			"delay": 1
		},
		"12": {
			"tankBasic": 12,
			"delay": 1
		},
		"25": {
			"swarmShip": 3,
			"delay": 3
		},
		"27": {
			"attackFighter": 4,
			"delay": 2
		}
	}, {
		"0": {
			"transporter": 10,
			"delay": 2
		},
		"10": {
			"lightFighter": 5,
			"delay": 2
		},
		"22": {
			"attackShip": 5,
			"delay": 2
		},
		"32": {
			"transporter": 15,
			"delay": 1
		}
	}, {
		"0": {
			"cruiser": 5,
			"delay": 5
		},
		"20": {
			"lightFighter": 20,
			"delay": 1
		},
		"22": {
			"cruiser": 6,
			"delay": 3
		},
		"40": {
			"attackShip": 10,
			"delay": 2
		},
		"45": {
			"mothership": 1,
			"delay": 1
		}
	}],
	"events": {
		"pre": {
			"ENABLE": ["repeater", "concept", "rifle", "turret", "annihilator"]
		}
	}
}
