import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import Season from './Season';

export default class UserScore extends Model {
  userId = Model.attribute<string | number>('userId');
  seasonId = Model.attribute<string | number | null>('seasonId');
  totalPoints = Model.attribute<number>('totalPoints');
  totalPicks = Model.attribute<number>('totalPicks');
  correctPicks = Model.attribute<number>('correctPicks');
  accuracy = Model.attribute<number>('accuracy');

  user = Model.hasOne<User | false>('user');
  season = Model.hasOne<Season | false>('season');
}