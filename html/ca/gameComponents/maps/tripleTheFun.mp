{
	"radius": 35,
	"offset": 0,
	"path": [
		[379, -117],
		[{
				"p1": 0.4,
				"p2": 0.3,
				"p3": 0.3,
				"start": [379, -74]
			},
			[
				[770, -74],
				[770, 295]
			],
			[],
			[
				[103, -74],
				[103, 625]
			]
		],
		[379, 402]
	],
	"baseRadius": 35,
	"baseLocation": [379, 402],
	"startingBaseHealth": 100,
	"maxBaseHealth": 100,
	"startingResources": 350,
	"waves": [{
		"0": {
			"scoutDroid": 10,
			"delay": 3
		},
		"30": {
			"lightFighter": 5,
			"delay": 2
		},
		"38": {
			"tankBasic": 5,
			"delay": 3
		}
	}, {
		"0": {
			"lightFighter": 10,
			"delay": 2
		},
		"15": {
			"tankBasic": 10,
			"delay": 3
		},
		"50": {
			"transporter": 3,
			"delay": 5
		}
	}, {
		"0": {
			"tankBasic": 10,
			"delay": 2
		},
		"22": {
			"swarmShip": 5,
			"delay": 3
		},
		"35": {
			"lightFighter": 10,
			"delay": 1
		}
	}, {
		"0": {
			"heavyFighter": 10,
			"delay": 3
		},
		"25": {
			"attackFighter": 10,
			"delay": 3
		},
		"50": {
			"attackShip": 2,
			"delay": 3
		}
	}, {
		"0": {
			"cruiser": 4,
			"delay": 5
		},
		"10": {
			"lightFighter": 10,
			"delay": 1
		},
		"20": {
			"attackFighter": 3,
			"delay": 2
		},
		"25": {
			"swarmShip": 3,
			"delay": 3
		},
		"30": {
			"mothership": 2,
			"delay": 5
		}
	}],
	"events": {
		"pre": {
			"ENABLE":["repeater", "concept", "rifle", "turret", "annihilator"]
		}
	}
}
