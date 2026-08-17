import Model from 'flarum/common/Model';
import Season from './Season';

export default class Competition extends Model {
  externalId = Model.attribute<string | null>('externalId');
  seasonId = Model.attribute<string | number | null>('seasonId');
  name = Model.attribute<string>('name');
  slug = Model.attribute<string>('slug');
  season = Model.hasOne<Season | false>('season');
}
