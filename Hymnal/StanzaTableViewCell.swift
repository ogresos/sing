//
//  stanzaTableViewCell.swift
//  Hymnal
//
//  Created by Jeremy Olson on 8/15/16.
//  Copyright © 2016 Jeremy Olson. All rights reserved.
//

import UIKit

class StanzaTableViewCell: UITableViewCell {

    @IBOutlet weak var stanzaTextView: UITextView!
    @IBOutlet weak var numberLabel: UILabel!
    
    override func awakeFromNib() {
        super.awakeFromNib()
        // Initialization code
    }

    override func setSelected(_ selected: Bool, animated: Bool) {
        super.setSelected(selected, animated: animated)

        
        // Configure the view for the selected state
    }

}
