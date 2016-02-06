{
	"radius": 35,
	"offset": 0,
	"path": [
		[75, 0],
		[75, 145],
		[96, 247],
		[140, 365],
		[216, 487],
		[336, 573],
		[508, 605],
		[676, 585],
		[766, 509],
		[814, 419],
		[824, 279],
		[780, 159],
		[682, 87],
		[564, 67],
		[424, 99],
		[348, 189],
		[312, 303],
		[366, 415],
		[472, 483],
		[604, 483],
		[688, 399],
		[696, 273],
		[554, 281]
	],
	"baseRadius": 35,
	"baseLocation": [554, 281],
	"startingBaseHealth": 100,
	"maxBaseHealth": 100,
	"startingResources": 150,
	"waves": [{
		"0": {
			"tankBasic": 15,
			"delay": 4
		},
		"45": {
			"lightFighter": 5,
			"delay": 4
		},
		"60": {
			"tankBasic": 10,
			"delay": 3
		}
	}, {
		"0": {
			"tankBasic": 20,
			"delay": 2
		},
		"35": {
			"lightFighter": 22,
			"delay": 2
		},
		"76": {
			"scoutDroid": 20,
			"delay": 2
		},
		"115": {
			"attackFighter": 2,
			"delay": 2
		}
	}, {
		"0": {
			"heavyFighter": 5,
			"delay": 8
		},
		"36": {
			"transporter": 10,
			"delay": 5
		},
		"88": {
			"attackShip": 2,
			"delay": 7
		}
	}, {
		"0": {
			"swarmShip": 5,
			"delay": 5
		},
		"26": {
			"cruiser": 2,
			"delay": 7
		},
		"40": {
			"tankBasic": 20,
			"delay": 1
		},
		"41": {
			"scoutDroid": 10,
			"delay": 2
		},
		"58": {
			"attackFighter": 5,
			"delay": 2
		}
	}, {
		"0": {
			"attackFighter": 5,
			"delay": 3
		},
		"10": {
			"swarmShip": 3,
			"delay": 3
		},
		"18": {
			"lightFighter": 15,
			"delay": 2
		},
		"45": {
			"cruiser": 3,
			"delay": 4
		},
		"65": {
			"heavyFighter": 4,
			"delay": 5
		},
		"75": {
			"attackShip": 5,
			"delay": 5
		},
		"100": {
			"scoutDroid": 15,
			"delay": 2
		},
		"105": {
			"mothership": 2,
			"delay": 2
		}
	}],
	"events": {
		"pre": {
			"ENABLE": ["repeater", "concept", "rifle", "turret", "annihilator"]
		}
	}
}
