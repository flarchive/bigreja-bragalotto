import Model from 'flarum/common/Model';
import Competition from './Competition';

export default class Season extends Model {
  name = Model.attribute<string>('name');
  slug = Model.attribute<string>('slug');
  startDate = Model.attribute<Date | null>('startDate', Model.transformDate);
  endDate = Model.attribute<Date | null>('endDate', Model.transformDate);
  competitions = Model.hasMany<Competition>('competitions');
}