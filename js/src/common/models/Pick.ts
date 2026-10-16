import Model from 'flarum/common/Model';
import PickemEvent from './Event';
import User from 'flarum/common/models/User';

export default class Pick extends Model {
  userId = Model.attribute<string | number>('userId');
  eventId = Model.attribute<string | number>('eventId');
  selectedOutcome = Model.attribute<'home' | 'away' | 'draw'>('selectedOutcome');
  isCorrect = Model.attribute<boolean | null>('isCorrect');
  
  event = Model.hasOne<PickemEvent | false>('event');
  user = Model.hasOne<User | false>('user');
}