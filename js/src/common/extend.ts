import Extend from 'flarum/common/extenders';
import Model from 'flarum/common/Model';

import Team from './models/Team';
import Season from './models/Season';
import Competition from './models/Competition';
import Week from './models/Week';
import PickemEvent from './models/Event';
import Pick from './models/Pick';
import UserScore from './models/UserScore';

export default [
  new Extend.Store()
    .add('bragalotto-teams', Team),
  
  new Extend.Model(Team)
    .attribute('name')
    .attribute('slug')
    .attribute('logoPath')
    .attribute('logoUrl'),

  new Extend.Store()
    .add('bragalotto-seasons', Season),
  
  new Extend.Model(Season)
    .attribute('name')
    .attribute('slug')
    .attribute<Date>('startDate', Model.transformDate)
    .attribute<Date>('endDate', Model.transformDate)
    .hasMany('competitions'),

  new Extend.Store()
    .add('bragalotto-competitions', Competition),

  new Extend.Model(Competition)
    .attribute('externalId')
    .attribute('seasonId')
    .attribute('name')
    .attribute('slug')
    .hasOne('season'),

  new Extend.Store()
    .add('bragalotto-weeks', Week),
  
  new Extend.Model(Week)
    .attribute('name')
    .attribute('competitionId')
    .attribute('weekNumber')
    .hasOne('competition'),

  new Extend.Store()
    .add('bragalotto-events', PickemEvent),
  
  new Extend.Model(PickemEvent)
    .attribute('weekId')
    .attribute('homeTeamId')
    .attribute('awayTeamId')
    .attribute<Date>('matchDate', Model.transformDate) // Tip ataması
    .attribute<Date>('cutoffDate', Model.transformDate) // Tip ataması
    .attribute('allowDraw')
    .attribute('status')
    .attribute('homeScore')
    .attribute('awayScore')
    .attribute('result')
    .attribute('canPick')
    .hasOne('week')
    .hasOne('homeTeam')
    .hasOne('awayTeam'),

  new Extend.Store()
    .add('bragalotto-picks', Pick),
  
  new Extend.Model(Pick)
    .attribute('userId')
    .attribute('eventId')
    .attribute('selectedOutcome')
    .attribute('isCorrect')
    .hasOne('event')
    .hasOne('user'),

  new Extend.Store()
    .add('bragalotto-user-scores', UserScore),
  
  new Extend.Model(UserScore)
    .attribute('userId')
    .attribute('seasonId')
    .attribute('totalPoints')
    .attribute('totalPicks')
    .attribute('correctPicks')
    .attribute('accuracy')
    .hasOne('user')
    .hasOne('season'),
];