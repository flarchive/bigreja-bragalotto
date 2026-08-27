import Model from 'flarum/common/Model';
import Competition from './Competition';

export default class Week extends Model {
  name = Model.attribute<string>('name');
  competitionId = Model.attribute<string | number | null>('competitionId');
  weekNumber = Model.attribute<number | null>('weekNumber');
  competition = Model.hasOne<Competition | false>('competition');
}