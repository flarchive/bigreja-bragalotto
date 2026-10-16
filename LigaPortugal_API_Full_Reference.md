# Liga Portugal API Full Reference

## Overview
Base URLs:
- https://www.ligaportugal.pt/api/v1/
- https://www.ligaportugal.pt/api/v2/

This document was derived from Liga Portugal frontend bundle analysis.

## Example: Seasons
### GET /api/v2/seasons

curl:
```bash
curl 'https://www.ligaportugal.pt/api/v2/seasons'
```

JavaScript:
```js
const data = await fetch('https://www.ligaportugal.pt/api/v2/seasons').then(r => r.json());
```

Sample response:
```json
[
  {
    "id": 20262027,
    "label": "2026/2027",
    "current": true
  }
]
```

## Competition Endpoints
- /api/v1/competition/{id}
- /api/v1/competition/matches
- /api/v1/competition/season/rounds
- /api/v1/competition/teams
- /api/v1/competition/matchweek/{round}/statistics
- /api/v1/competition/round/{round}/nominations
- /api/v1/competition/pitch/ratings
- /api/v1/competition/stadiums/rating
- /api/v1/competition/spectators/statistics
- /api/v1/competition/usefultime
- /api/v2/competition/standings
- /api/v2/competition/top/players

### GET /api/v1/competition/matches
```bash
curl 'https://www.ligaportugal.pt/api/v1/competition/matches?competition=ligaportugalbetclic&season=20262027&round=1'
```

```json
[
 {
  "id":12345,
  "homeTeam":{"id":3,"name":"Sporting CP"},
  "awayTeam":{"id":5,"name":"FC Porto"},
  "homeTeamGoals":0,
  "awayTeamGoals":0
 }
]
```

### GET /api/v2/competition/standings
```bash
curl 'https://www.ligaportugal.pt/api/v2/competition/standings?competition=ligaportugalbetclic&season=20262027&round=1&live=true'
```

```json
{
 "lastRound":"1",
 "standings":[{
   "key":"Overall",
   "standings":[{
      "position":1,
      "points":3,
      "matches":1,
      "wins":1
   }]
 }]
}
```

## Match Endpoints
- /api/v1/match/details
- /api/v1/match/info/static
- /api/v1/match/formations
- /api/v1/match/mvp
- /api/v1/match/climate
- /api/v1/match/teams/history
- /api/v2/match/info/dynamic

### GET /api/v1/match/details
```bash
curl 'https://www.ligaportugal.pt/api/v1/match/details?competition=ligaportugalbetclic&season=20262027&round=1&fixture=12345'
```

```json
{
 "id":12345,
 "status":"LIVE",
 "minute":54,
 "homeTeamGoals":2,
 "awayTeamGoals":1
}
```

## Team Endpoints
- /api/v1/team/info
- /api/v1/team/squad
- /api/v1/team/matches
- /api/v1/team/statistics
- /api/v1/team/standings
- /api/v1/team/{id}/stadium
- /api/v2/team/leaders

### GET /api/v1/team/info
```bash
curl 'https://www.ligaportugal.pt/api/v1/team/info?id=3&season=20262027'
```

```json
{
 "id":3,
 "name":"Sporting CP"
}
```

## Player Endpoints
- /api/v1/player/{id}
- /api/v1/player/{id}/history
- /api/v1/player/{id}/matches
- /api/v1/player/{id}/videos
- /api/v1/player/{id}/season/statistics
- /api/v1/player/{id}/competition/statistics

## Search Endpoints
- /api/v1/search/news/{term}
- /api/v1/search/videos/{term}

Example:
```bash
curl 'https://www.ligaportugal.pt/api/v1/search/news/benfica'
```

## International Endpoints
- /api/v2/international/seasons
- /api/v2/international/competition/{id}/rounds
- /api/v2/international/competition/{id}/standings
- /api/v2/international/competition/{id}/stages/{stage}/rounds/{round}/matches

## Authentication-related Endpoints
- /api/v1/account
- /api/v1/profile
- /api/v1/profile/newsletters
- /api/v1/profile/favourite/team
- /api/v1/profile/favourite/teams

## Copilot Agent Guidance
- Treat schemas as inferred.
- Log unknown fields.
- Make all properties optional.
- Preserve raw JSON payloads.
- Generate TypeScript interfaces from live payloads when discovered.
