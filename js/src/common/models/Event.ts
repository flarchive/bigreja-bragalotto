import Model from 'flarum/common/Model';
import Week from './Week';
import Team from './Team';

export default class PickemEvent extends Model {
  weekId = Model.attribute<string | number | null>('weekId');
  homeTeamId = Model.attribute<string | number>('homeTeamId');
  awayTeamId = Model.attribute<string | number>('awayTeamId');
  matchDate = Model.attribute<Date | null>('matchDate', Model.transformDate);
  cutoffDate = Model.attribute<Date | null>('cutoffDate', Model.transformDate);
  allowDraw = Model.attribute<boolean>('allowDraw');
  status = Model.attribute<'scheduled' | 'closed' | 'finished'>('status');
  homeScore = Model.attribute<number | null>('homeScore');
  awayScore = Model.attribute<number | null>('awayScore');
  result = Model.attribute<'home' | 'away' | 'draw' | null>('result');
  canPick = Model.attribute<boolean>('canPick');

  week = Model.hasOne<Week | false>('week');
  homeTeam = Model.hasOne<Team | false>('homeTeam');
  awayTeam = Model.hasOne<Team | false>('awayTeam');
}